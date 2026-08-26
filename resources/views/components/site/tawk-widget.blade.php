@php
    $enabled = \App\Models\Setting::get('tawk_enabled') === '1';
    $propertyId = \App\Models\Setting::get('tawk_property_id');
    $widgetId = \App\Models\Setting::get('tawk_widget_id');
@endphp

@if ($enabled && filled($propertyId) && filled($widgetId))
    {{--
        Tawk.to live chat.

        data-navigate-once keeps Livewire from re-running this on every
        wire:navigate page change, and the Tawk_API guard covers a full reload
        landing on a page where the embed is already present.
    --}}
    <script data-navigate-once>
        (function () {
            if (window.Tawk_API && window.Tawk_API.onLoaded) {
                return;
            }

            window.Tawk_API = window.Tawk_API || {};
            window.Tawk_LoadStart = new Date();

            var script = document.createElement('script');
            var first = document.getElementsByTagName('script')[0];

            script.async = true;
            script.src = 'https://embed.tawk.to/{{ $propertyId }}/{{ $widgetId }}';
            script.charset = 'UTF-8';
            script.setAttribute('crossorigin', '*');

            first.parentNode.insertBefore(script, first);
        })();
    </script>
@endif
