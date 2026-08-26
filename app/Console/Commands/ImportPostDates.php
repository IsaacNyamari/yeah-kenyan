<?php

namespace App\Console\Commands;

use App\Models\Post;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

/**
 * Restores the original publish dates on the imported news articles.
 *
 * The legacy site rendered relative labels ("1 yr ago") and never emitted the
 * underlying timestamp, so the real dates cannot be scraped back off the HTML.
 * They do still exist in the legacy database, so this reads them from an
 * export of that table and matches rows to posts by title.
 *
 * Accepts CSV or JSON. Column names are flexible: any of title/post_title/name
 * for the headline, and any of published_at/created_at/date/post_date for the
 * timestamp.
 */
class ImportPostDates extends Command
{
    protected $signature = 'news:dates
                            {file? : CSV or JSON export of the legacy posts table}
                            {--approximate : Backdate to roughly a year ago instead, when no export is available}
                            {--dry-run : Show what would change without saving}';

    protected $description = 'Restore original publish dates on imported news articles';

    /**
     * Candidate column names, most specific first.
     *
     * @var list<string>
     */
    private const TITLE_KEYS = ['title', 'post_title', 'headline', 'name'];

    private const DATE_KEYS = ['published_at', 'post_date', 'created_at', 'date', 'created'];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($this->option('approximate')) {
            return $this->applyApproximateDates($dryRun);
        }

        $file = $this->argument('file');

        if (blank($file)) {
            $this->components->error('Provide an export file, or pass --approximate.');
            $this->line('  Export the legacy <options=bold>posts</> table from cPanel → phpMyAdmin as CSV, then:');
            $this->line('  <fg=gray>php artisan news:dates storage/app/legacy-posts.csv</>');

            return self::FAILURE;
        }

        if (! is_file($file)) {
            $this->components->error("File not found: $file");

            return self::FAILURE;
        }

        $rows = $this->readRows($file);

        if ($rows === []) {
            $this->components->error('No usable rows found. Expected a title column and a date column.');

            return self::FAILURE;
        }

        return $this->applyDates($rows, $dryRun);
    }

    /**
     * @return array<string, CarbonImmutable> slug => date
     */
    private function readRows(string $file): array
    {
        $raw = str_ends_with(strtolower($file), '.json')
            ? $this->readJson($file)
            : $this->readCsv($file);

        $dates = [];

        foreach ($raw as $row) {
            $row = array_change_key_case((array) $row);

            $title = $this->firstKey($row, self::TITLE_KEYS);
            $date = $this->firstKey($row, self::DATE_KEYS);

            if (blank($title) || blank($date)) {
                continue;
            }

            try {
                $dates[Str::slug($title)] = CarbonImmutable::parse($date);
            } catch (Throwable) {
                $this->components->warn('Unparseable date for: '.Str::limit($title, 50));
            }
        }

        return $dates;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readJson(string $file): array
    {
        $decoded = json_decode((string) file_get_contents($file), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readCsv(string $file): array
    {
        $handle = fopen($file, 'r');

        if ($handle === false) {
            return [];
        }

        $headers = fgetcsv($handle) ?: [];
        $rows = [];

        while (($line = fgetcsv($handle)) !== false) {
            if (count($line) === count($headers)) {
                $rows[] = array_combine($headers, $line);
            }
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @param  array<int, string>  $keys
     */
    private function firstKey(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (filled($row[$key] ?? null)) {
                return (string) $row[$key];
            }
        }

        return null;
    }

    /**
     * @param  array<string, CarbonImmutable>  $dates
     */
    private function applyDates(array $dates, bool $dryRun): int
    {
        $matched = 0;
        $missed = [];

        foreach (Post::all() as $post) {
            $date = $dates[$post->slug] ?? null;

            if ($date === null) {
                $missed[] = $post->title;

                continue;
            }

            $this->components->twoColumnDetail(
                Str::limit($post->title, 52),
                $post->published_at?->format('Y-m-d').' → <fg=green>'.$date->format('Y-m-d').'</>',
            );

            if (! $dryRun) {
                $post->forceFill(['published_at' => $date])->save();
            }

            $matched++;
        }

        $this->newLine();
        $this->components->info(sprintf('%d matched%s, %d unmatched.', $matched, $dryRun ? ' (dry run)' : ' and updated', count($missed)));

        foreach ($missed as $title) {
            $this->components->warn('No date found for: '.Str::limit($title, 60));
        }

        return self::SUCCESS;
    }

    /**
     * Fall back to spacing the posts across the month around a year ago.
     *
     * This is an estimate built from the only signal the old site gave — every
     * article was labelled "1 yr ago" — and preserves the existing ordering.
     * It is not the real publication history.
     */
    private function applyApproximateDates(bool $dryRun): int
    {
        $posts = Post::orderBy('id')->get();

        if ($posts->isEmpty()) {
            $this->components->warn('No posts to backdate.');

            return self::SUCCESS;
        }

        $this->components->warn('These dates are estimates, not the original timestamps.');
        $this->newLine();

        $start = CarbonImmutable::now()->subYear();

        foreach ($posts->values() as $index => $post) {
            // Two-day spacing keeps the original order legible in the listing.
            $date = $start->addDays($index * 2)->setTime(9, 0);

            $this->components->twoColumnDetail(
                Str::limit($post->title, 52),
                '<fg=yellow>~ '.$date->format('Y-m-d').'</>',
            );

            if (! $dryRun) {
                $post->forceFill(['published_at' => $date])->save();
            }
        }

        $this->newLine();
        $this->components->info(sprintf(
            '%d post(s) backdated%s. Re-run with an export to set the real dates.',
            $posts->count(),
            $dryRun ? ' (dry run)' : '',
        ));

        return self::SUCCESS;
    }
}
