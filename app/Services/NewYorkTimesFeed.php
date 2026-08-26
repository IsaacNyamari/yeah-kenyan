<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\NewsItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Live headlines from the New York Times Top Stories API.
 *
 * Nothing here is written to the database. Items are fetched on demand, held
 * in the cache briefly, and rendered as links back to nytimes.com — only the
 * headline and the abstract the API supplies for display are used, with the
 * publisher named on every card.
 *
 * The API allows 500 requests a day and 5 a minute, so the cache is doing real
 * work rather than shaving milliseconds.
 */
class NewYorkTimesFeed
{
    private const ENDPOINT = 'https://api.nytimes.com/svc/topstories/v2';

    /**
     * Sections the Top Stories API publishes.
     *
     * @var list<string>
     */
    public const SECTIONS = [
        'home', 'arts', 'automobiles', 'books', 'business', 'fashion', 'food',
        'health', 'insider', 'magazine', 'movies', 'nyregion', 'obituaries',
        'opinion', 'politics', 'realestate', 'science', 'sports', 'sundayreview',
        'technology', 'theater', 't-magazine', 'travel', 'upshot', 'us', 'world',
    ];

    private ?string $error = null;

    public function isEnabled(): bool
    {
        return Setting::boolean('nyt_enabled') && filled($this->apiKey());
    }

    public function error(): ?string
    {
        return $this->error;
    }

    /**
     * Headlines for the configured section.
     *
     * @return list<NewsItem>
     */
    public function headlines(?int $limit = null): array
    {
        if (! $this->isEnabled()) {
            return [];
        }

        $section = $this->section();
        $limit ??= (int) (Setting::get('nyt_limit') ?: 6);

        // Cache the plain API rows, not NewsItem objects: cached objects are
        // unserialized before the class is autoloaded, which yields
        // __PHP_Incomplete_Class and takes the page down.
        $results = Cache::remember(
            "nyt.topstories.$section",
            now()->addMinutes(15),
            fn (): array => $this->request($section),
        );

        $items = array_values(array_filter(array_map(
            fn (array $result): ?NewsItem => NewsItem::fromNewYorkTimes($result),
            array_filter($results, 'is_array'),
        )));

        return array_slice($items, 0, max(0, $limit));
    }

    /**
     * Fetch the raw result rows, degrading to an empty list on any failure.
     *
     * @return list<array<string, mixed>>
     */
    private function request(string $section): array
    {
        try {
            $response = Http::timeout(12)
                ->retry(2, 300)
                ->get(self::ENDPOINT."/$section.json", ['api-key' => $this->apiKey()]);

            if (! $response->successful()) {
                $this->error = "New York Times API returned {$response->status()}.";

                return [];
            }

            $results = $response->json('results');

            if (! is_array($results)) {
                return [];
            }

            return array_values(array_filter($results, 'is_array'));
        } catch (Throwable $e) {
            $this->error = $e->getMessage();

            report($e);

            return [];
        }
    }

    /**
     * Verify a key and section without disturbing the cached feed.
     *
     * @return array{ok: bool, message: string}
     */
    public function check(string $apiKey, string $section = 'home'): array
    {
        try {
            $response = Http::timeout(12)->get(self::ENDPOINT."/$section.json", ['api-key' => $apiKey]);

            if ($response->successful()) {
                return ['ok' => true, 'message' => sprintf('%d headlines available.', count((array) $response->json('results', [])))];
            }

            return ['ok' => false, 'message' => match ($response->status()) {
                401 => 'That API key was rejected.',
                429 => 'Rate limit reached — try again shortly.',
                default => "The API returned {$response->status()}.",
            }];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => str($e->getMessage())->limit(120)->toString()];
        }
    }

    public function flush(): void
    {
        foreach (self::SECTIONS as $section) {
            Cache::forget("nyt.topstories.$section");
        }
    }

    private function section(): string
    {
        $section = (string) (Setting::get('nyt_section') ?: 'home');

        return in_array($section, self::SECTIONS, true) ? $section : 'home';
    }

    private function apiKey(): ?string
    {
        return Setting::get('nyt_api_key');
    }
}
