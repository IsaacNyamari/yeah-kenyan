@php
    $measurementId = \App\Models\Setting::get('analytics_measurement_id');

    // Local and test traffic would otherwise pollute the real reports.
    $shouldTrack = filled($measurementId)
        && \App\Models\Setting::boolean('analytics_tracking_enabled')
        && ! app()->environment('local', 'testing');
@endphp

@if ($shouldTrack)
    {{-- Google tag (gtag.js) --}}
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $measurementId }}" data-navigate-once></script>
    <script data-navigate-once>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        /*
         * send_page_view is disabled here and fired manually below.
         *
         * Livewire's wire:navigate swaps the page without a document load, so
         * the automatic pageview would only ever record the first page someone
         * landed on and nothing they clicked through to afterwards.
         */
        gtag('config', @js($measurementId), { send_page_view: false });

        function reportPageView() {
            gtag('event', 'page_view', {
                page_location: window.location.href,
                page_path: window.location.pathname + window.location.search,
                page_title: document.title,
            });
        }

        reportPageView();
        document.addEventListener('livewire:navigated', reportPageView);
    </script>
@endif
