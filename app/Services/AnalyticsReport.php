<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Spatie\Analytics\Facades\Analytics;
use Spatie\Analytics\Period;
use Throwable;

/**
 * Thin wrapper over spatie/laravel-analytics.
 *
 * Google Analytics needs a property id and a service-account key, neither of
 * which exists on a fresh install. Rather than letting the dashboard explode,
 * every call goes through here: unconfigured or failing lookups come back as
 * empty collections and the screen renders a setup guide instead.
 */
class AnalyticsReport
{
    private ?string $error = null;

    /**
     * True when both the property id and credentials are present.
     */
    public function isConfigured(): bool
    {
        return filled(config('analytics.property_id')) && $this->hasCredentials();
    }

    /**
     * The reason analytics is unavailable, if it is.
     */
    public function error(): ?string
    {
        return $this->error;
    }

    /**
     * Which prerequisite is missing, for the setup guide.
     *
     * @return array{property_id: bool, credentials: bool}
     */
    public function readiness(): array
    {
        return [
            'property_id' => filled(config('analytics.property_id')),
            'credentials' => $this->hasCredentials(),
        ];
    }

    /**
     * Visitors and page views per day.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function visitorsByDate(int $days): Collection
    {
        return $this->attempt(fn (): Collection => Analytics::fetchVisitorsAndPageViews(Period::days($days)));
    }

    /**
     * Site-wide totals for the period.
     *
     * @return array{visitors: int, pageViews: int}
     */
    public function totals(int $days): array
    {
        $rows = $this->attempt(fn (): Collection => Analytics::fetchTotalVisitorsAndPageViews(Period::days($days)));

        return [
            'visitors' => (int) $rows->sum('activeUsers'),
            'pageViews' => (int) $rows->sum('screenPageViews'),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function topPages(int $days, int $limit = 10): Collection
    {
        return $this->attempt(fn (): Collection => Analytics::fetchMostVisitedPages(Period::days($days), $limit));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function topReferrers(int $days, int $limit = 10): Collection
    {
        return $this->attempt(fn (): Collection => Analytics::fetchTopReferrers(Period::days($days), $limit));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function topCountries(int $days, int $limit = 10): Collection
    {
        return $this->attempt(fn (): Collection => Analytics::fetchTopCountries(Period::days($days), $limit));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function topBrowsers(int $days, int $limit = 8): Collection
    {
        return $this->attempt(fn (): Collection => Analytics::fetchTopBrowsers(Period::days($days), $limit));
    }

    /**
     * Run a lookup, swallowing configuration and transport failures so one bad
     * call cannot take the whole page down.
     *
     * @param  callable(): Collection<int, array<string, mixed>>  $query
     * @return Collection<int, array<string, mixed>>
     */
    private function attempt(callable $query): Collection
    {
        if (! $this->isConfigured()) {
            return collect();
        }

        try {
            return $query();
        } catch (Throwable $e) {
            $this->error ??= $e->getMessage();

            report($e);

            return collect();
        }
    }

    private function hasCredentials(): bool
    {
        $credentials = config('analytics.service_account_credentials_json');

        if (is_array($credentials)) {
            return $credentials !== [];
        }

        return is_string($credentials) && is_file($credentials);
    }
}
