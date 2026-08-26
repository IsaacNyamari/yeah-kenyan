<?php

namespace App\Support;

use App\Models\Post;
use Carbon\CarbonImmutable;

/**
 * One item in the news listing, from either source.
 *
 * Our own articles live in the database and open on this site. Wire items are
 * fetched live, never stored, and always link back to the publisher — so the
 * listing can mix them while keeping the distinction explicit.
 */
readonly class NewsItem
{
    public function __construct(
        public string $title,
        public ?string $excerpt,
        public string $url,
        public ?string $imageUrl,
        public string $categoryName,
        public ?string $author,
        public ?CarbonImmutable $publishedAt,
        public bool $isExternal = false,
        public ?string $source = null,
    ) {}

    public static function fromPost(Post $post): self
    {
        return new self(
            title: $post->title,
            excerpt: $post->excerpt,
            url: route('news.show', $post->slug),
            imageUrl: $post->image_url,
            categoryName: $post->category->name,
            author: $post->author,
            publishedAt: $post->published_at,
            isExternal: false,
        );
    }

    /**
     * Map one New York Times Top Stories result.
     *
     * Only the headline, the abstract the API supplies for display, and a link
     * back are carried across. Nothing is stored.
     *
     * @param  array<string, mixed>  $result
     */
    public static function fromNewYorkTimes(array $result): ?self
    {
        $title = trim((string) ($result['title'] ?? ''));
        $url = trim((string) ($result['url'] ?? ''));

        if ($title === '' || $url === '') {
            return null;
        }

        return new self(
            title: $title,
            excerpt: trim((string) ($result['abstract'] ?? '')) ?: null,
            url: $url,
            imageUrl: self::largestImage($result['multimedia'] ?? null),
            categoryName: ucfirst((string) ($result['section'] ?? 'News')),
            // "By Jane Doe" reads oddly next to our own author names.
            author: trim(preg_replace('/^By\s+/i', '', (string) ($result['byline'] ?? '')) ?? '') ?: null,
            publishedAt: self::parseDate($result['published_date'] ?? null),
            isExternal: true,
            source: 'The New York Times',
        );
    }

    /**
     * Pick the widest available image.
     */
    private static function largestImage(mixed $multimedia): ?string
    {
        if (! is_array($multimedia) || $multimedia === []) {
            return null;
        }

        // Newer responses return a single object rather than a list.
        if (isset($multimedia['url'])) {
            return (string) $multimedia['url'];
        }

        $images = array_filter($multimedia, fn ($item): bool => is_array($item) && filled($item['url'] ?? null));

        if ($images === []) {
            return null;
        }

        usort($images, fn (array $a, array $b): int => ((int) ($b['width'] ?? 0)) <=> ((int) ($a['width'] ?? 0)));

        return (string) $images[0]['url'];
    }

    private static function parseDate(mixed $value): ?CarbonImmutable
    {
        if (blank($value)) {
            return null;
        }

        try {
            return CarbonImmutable::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }

    public function sortKey(): int
    {
        return $this->publishedAt?->getTimestamp() ?? 0;
    }

    /**
     * External links open in a new tab and must not pass referrer trust on.
     *
     * @return array<string, string>
     */
    public function linkAttributes(): array
    {
        return $this->isExternal
            ? ['target' => '_blank', 'rel' => 'noopener noreferrer']
            : ['wire:navigate' => 'true'];
    }
}
