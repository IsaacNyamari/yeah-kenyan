<?php

use App\Services\AnalyticsReport;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Analytics')] class extends Component {
    #[Url]
    public int $days = 30;

    /**
     * Selectable reporting windows.
     *
     * @var array<int, string>
     */
    public array $ranges = [7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days'];

    public function report(): AnalyticsReport
    {
        return app(AnalyticsReport::class);
    }

    #[Computed]
    public function configured(): bool
    {
        return $this->report()->isConfigured();
    }

    /**
     * @return array{property_id: bool, credentials: bool}
     */
    #[Computed]
    public function readiness(): array
    {
        return $this->report()->readiness();
    }

    /**
     * @return array{visitors: int, pageViews: int}
     */
    #[Computed]
    public function totals(): array
    {
        return $this->report()->totals($this->days);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function timeline(): Collection
    {
        return $this->report()->visitorsByDate($this->days);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function topPages(): Collection
    {
        return $this->report()->topPages($this->days);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function topReferrers(): Collection
    {
        return $this->report()->topReferrers($this->days);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function topCountries(): Collection
    {
        return $this->report()->topCountries($this->days);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function topBrowsers(): Collection
    {
        return $this->report()->topBrowsers($this->days);
    }
}; ?>

<div class="space-y-6">

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">Analytics</flux:heading>
            <flux:text class="mt-1">Website traffic from Google Analytics.</flux:text>
        </div>

        @if ($this->configured)
            <flux:select wire:model.live="days" class="max-w-48">
                @foreach ($ranges as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        @endif
    </div>

    @if (! $this->configured)
        {{-- Setup guide: shown until a property id and service-account key exist. --}}
        <flux:card>
            <div class="flex items-start gap-4">
                <span class="rounded-lg bg-amber-50 p-2.5 text-amber-600 dark:bg-amber-900/30">
                    <flux:icon.exclamation-triangle class="size-5" />
                </span>

                <div class="flex-1">
                    <flux:heading size="lg">Google Analytics is not connected yet</flux:heading>
                    <flux:text class="mt-1">
                        Two things are needed before traffic data can be shown.
                    </flux:text>

                    <div class="mt-6 space-y-4">
                        <div class="flex items-start gap-3">
                            @if ($this->readiness['property_id'])
                                <flux:icon.check-circle class="mt-0.5 size-5 shrink-0 text-leaf-600" />
                            @else
                                <flux:icon.x-circle class="mt-0.5 size-5 shrink-0 text-zinc-400" />
                            @endif
                            <div>
                                <p class="font-semibold">A GA4 property ID</p>
                                <flux:text size="sm" class="mt-0.5">
                                    Find it in Google Analytics under Admin &rarr; Property Settings, then paste it
                                    into Settings.
                                </flux:text>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            @if ($this->readiness['credentials'])
                                <flux:icon.check-circle class="mt-0.5 size-5 shrink-0 text-leaf-600" />
                            @else
                                <flux:icon.x-circle class="mt-0.5 size-5 shrink-0 text-zinc-400" />
                            @endif
                            <div>
                                <p class="font-semibold">A service-account key</p>
                                <flux:text size="sm" class="mt-0.5">
                                    Create a service account in Google Cloud, enable the Google Analytics Data API,
                                    grant it Viewer access on the property, then paste its JSON key into Settings.
                                </flux:text>
                            </div>
                        </div>
                    </div>

                    <flux:button :href="route('admin.settings').'?tab=analytics'" wire:navigate
                                 variant="primary" icon="wrench-screwdriver" class="mt-6">
                        Open analytics settings
                    </flux:button>

                    @if ($this->report()->error())
                        <flux:callout variant="danger" class="mt-6">
                            <flux:callout.heading>Google returned an error</flux:callout.heading>
                            <flux:callout.text>{{ $this->report()->error() }}</flux:callout.text>
                        </flux:callout>
                    @endif

                    <flux:text size="sm" class="mt-6">
                        Until then, the <flux:link :href="route('dashboard')" wire:navigate>dashboard</flux:link>
                        still reports on your own content and enquiries.
                    </flux:text>
                </div>
            </div>
        </flux:card>
    @else
        {{-- Totals --}}
        <div class="grid gap-4 sm:grid-cols-2">
            <x-admin.stat-tile
                label="Visitors"
                :value="$this->totals['visitors']"
                :meta="$ranges[$days] ?? ''"
                icon="users"
                tone="brand" />

            <x-admin.stat-tile
                label="Page views"
                :value="$this->totals['pageViews']"
                :meta="$ranges[$days] ?? ''"
                icon="eye"
                tone="leaf" />
        </div>

        {{-- Traffic over time --}}
        <flux:card>
            <flux:heading size="lg">Traffic</flux:heading>

            @php
                $timeline = $this->timeline;
                $peak = max(1, (int) $timeline->max('screenPageViews'));
            @endphp

            @if ($timeline->isEmpty())
                <flux:text class="mt-4">No traffic recorded in this period.</flux:text>
            @else
                <div class="mt-6 flex h-48 items-end gap-1">
                    @foreach ($timeline as $row)
                        <div class="group flex flex-1 flex-col items-center gap-1">
                            <div class="flex w-full flex-1 items-end justify-center">
                                <div class="w-full rounded-t bg-brand-500 transition-all group-hover:bg-brand-600"
                                     style="height: {{ max(2, round(($row['screenPageViews'] ?? 0) / $peak * 100)) }}%"
                                     title="{{ ($row['date'] ?? null)?->format('M j') ?? '' }}: {{ $row['screenPageViews'] ?? 0 }} views, {{ $row['activeUsers'] ?? 0 }} visitors"></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-2 flex justify-between text-xs text-zinc-500">
                    <span>{{ ($timeline->first()['date'] ?? null)?->format('M j') }}</span>
                    <span>{{ ($timeline->last()['date'] ?? null)?->format('M j') }}</span>
                </div>
            @endif
        </flux:card>

        <div class="grid gap-6 lg:grid-cols-2">
            <x-admin.analytics-list
                title="Top pages"
                :rows="$this->topPages"
                label-key="fullPageUrl"
                value-key="screenPageViews"
                empty="No page data yet." />

            <x-admin.analytics-list
                title="Top referrers"
                :rows="$this->topReferrers"
                label-key="pageReferrer"
                value-key="screenPageViews"
                empty="No referrer data yet." />

            <x-admin.analytics-list
                title="Top countries"
                :rows="$this->topCountries"
                label-key="country"
                value-key="screenPageViews"
                empty="No country data yet." />

            <x-admin.analytics-list
                title="Top browsers"
                :rows="$this->topBrowsers"
                label-key="browser"
                value-key="screenPageViews"
                empty="No browser data yet." />
        </div>
    @endif
</div>
