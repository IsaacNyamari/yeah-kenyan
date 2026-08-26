<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Post;
use App\Services\ArticleHtml;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Migrates the news articles from the legacy PHP site into the posts table.
 *
 * The old blog addressed articles by an opaque token; this walks the listing
 * pages, resolves every article, and re-keys them on a readable slug. Images
 * were already pulled down into public/uploads during the rebuild, so they are
 * matched by filename rather than re-downloaded.
 *
 * Idempotent: re-running updates existing rows rather than duplicating them.
 */
class ImportLegacyPosts extends Command
{
    protected $signature = 'news:import
                            {--base=https://yeahkenyan.com : Root URL of the legacy site}
                            {--dry-run : Report what would be imported without writing}';

    protected $description = 'Import news articles from the legacy PHP site into the posts table';

    public function handle(): int
    {
        $base = rtrim((string) $this->option('base'), '/');
        $dryRun = (bool) $this->option('dry-run');

        $urls = $this->discoverArticleUrls($base);

        if ($urls === []) {
            $this->components->error('No articles found on the legacy site.');

            return self::FAILURE;
        }

        $this->components->info(sprintf('Found %d article(s).', count($urls)));

        $imported = 0;
        $skipped = 0;

        foreach ($urls as $url) {
            $article = $this->extractArticle($url);

            if ($article === null) {
                $this->components->warn("Could not parse: $url");
                $skipped++;

                continue;
            }

            if ($dryRun) {
                $this->line(sprintf('  <fg=green>would import</> %s [%s]', Str::limit($article['title'], 60), $article['category']));
                $imported++;

                continue;
            }

            $this->storeArticle($article);

            $this->line(sprintf('  <fg=green>imported</> %s', Str::limit($article['title'], 60)));
            $imported++;
        }

        $this->newLine();
        $this->components->info(sprintf('%d imported, %d skipped.', $imported, $skipped));

        return self::SUCCESS;
    }

    /**
     * Walk the listing pages and collect every distinct article URL.
     *
     * @return list<string>
     */
    private function discoverArticleUrls(string $base): array
    {
        $listings = ["$base/", "$base/blog/"];

        // The listing is also filtered by category token, so pull those too.
        $tokens = [];

        foreach ($listings as $listing) {
            $html = $this->fetch($listing);

            if ($html === null) {
                continue;
            }

            preg_match_all('/post\.php\?token=([a-f0-9]+)/i', $html, $matches);
            $tokens = [...$tokens, ...$matches[1]];

            preg_match_all('/blog\/\?token=([a-f0-9]+)/i', $html, $categoryMatches);

            foreach (array_unique($categoryMatches[1]) as $categoryToken) {
                $categoryHtml = $this->fetch("$base/blog/?token=$categoryToken");

                if ($categoryHtml === null) {
                    continue;
                }

                preg_match_all('/post\.php\?token=([a-f0-9]+)/i', $categoryHtml, $inner);
                $tokens = [...$tokens, ...$inner[1]];
            }
        }

        return array_map(
            fn (string $token): string => "$base/blog/post.php?token=$token",
            array_values(array_unique($tokens)),
        );
    }

    private function fetch(string $url): ?string
    {
        $response = Http::withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; YeahKenyanImporter/1.0)'])
            ->timeout(45)
            ->retry(2, 500)
            ->get($url);

        return $response->successful() ? $response->body() : null;
    }

    /**
     * Pull the article fields out of a legacy post page.
     *
     * @return array{title: string, category: string, body: string, image: ?string}|null
     */
    private function extractArticle(string $url): ?array
    {
        $html = $this->fetch($url);

        if ($html === null) {
            return null;
        }

        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        $xpath = new DOMXPath($document);

        $title = $this->firstText($xpath, "//h1[contains(@class,'text-secondary')]");

        if ($title === null) {
            return null;
        }

        return [
            'title' => $title,
            'category' => $this->firstText($xpath, "//a[contains(@class,'badge')]") ?? 'Latest News',
            'body' => $this->extractBody($document, $xpath),
            'image' => $this->resolveImage($xpath),
        ];
    }

    /**
     * Collect the article body from the content container.
     *
     * The legacy markup nests <p> inside <p>, which is invalid, so the parser
     * auto-closes the outer wrapper and its children come back empty. Reading
     * the container and taking everything after the headline sidesteps that.
     */
    private function extractBody(DOMDocument $document, DOMXPath $xpath): string
    {
        $container = $xpath->query("//div[contains(@class,'border-top-0')]")->item(0);

        if (! $container instanceof DOMElement) {
            return '';
        }

        $html = '';
        $reachedHeadline = false;

        foreach ($container->childNodes as $child) {
            if ($child instanceof DOMElement && $child->tagName === 'h1') {
                $reachedHeadline = true;

                continue;
            }

            if ($reachedHeadline) {
                $html .= $document->saveHTML($child);
            }
        }

        return app(ArticleHtml::class)->sanitize($html);
    }

    private function firstText(DOMXPath $xpath, string $query): ?string
    {
        $node = $xpath->query($query)->item(0);

        if ($node === null) {
            return null;
        }

        $text = trim(preg_replace('/\s+/u', ' ', $node->textContent) ?? '');

        return $text === '' ? null : $text;
    }

    /**
     * Match the article's hero image against the files already in public/uploads.
     */
    private function resolveImage(DOMXPath $xpath): ?string
    {
        $node = $xpath->query("//div[contains(@class,'position-relative')]/img[contains(@class,'img-fluid')]")->item(0);

        if (! $node instanceof DOMElement) {
            return null;
        }

        $name = rawurldecode(basename($node->getAttribute('src')));

        if (is_file(public_path("uploads/$name"))) {
            return "uploads/$name";
        }

        // A few legacy filenames carried characters that are illegal on Windows
        // and were normalised during the image migration.
        $normalised = preg_replace('/[^A-Za-z0-9.\-]+/', '-', $name) ?? $name;

        foreach (glob(public_path('uploads/*')) ?: [] as $candidate) {
            $candidateName = basename($candidate);

            if (Str::slug(pathinfo($candidateName, PATHINFO_FILENAME)) === Str::slug(pathinfo($normalised, PATHINFO_FILENAME))) {
                return "uploads/$candidateName";
            }
        }

        return null;
    }

    /**
     * @param  array{title: string, category: string, body: string, image: ?string}  $article
     */
    private function storeArticle(array $article): void
    {
        $category = Category::firstOrCreate(
            ['slug' => Str::slug($article['category'])],
            ['name' => Str::title($article['category'])],
        );

        $slug = Str::slug($article['title']);

        $excerpt = app(ArticleHtml::class)->excerpt($article['body']);

        Post::updateOrCreate(
            ['slug' => $slug],
            [
                'category_id' => $category->id,
                'title' => $article['title'],
                'author' => 'Yeah Kenyan',
                'excerpt' => $excerpt,
                'body' => $article['body'],
                'image' => $article['image'],
                'published_at' => now(),
            ],
        );
    }
}
