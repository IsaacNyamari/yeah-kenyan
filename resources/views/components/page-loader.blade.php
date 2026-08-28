{{--
    Brand loader, shared by the public site and the dashboard.

    Two indicators drive off the same image. A full-screen overlay covers page
    loads and wire:navigate transitions, and a small corner badge reports
    Livewire round trips.

    The badge is deliberately delayed. Most commits finish in well under the
    threshold below, and flashing a loader on every keystroke of a live-preview
    field reads as jitter rather than feedback.
--}}

@persist('page-loader')
    <div>
        <div id="page-preloader" role="status" aria-live="polite">
            <img src="{{ asset('images/loader.gif') }}" alt="" width="96" height="96" fetchpriority="high">
            <span class="sr-only">{{ __('Loading') }}</span>
        </div>

        <div id="livewire-activity" role="status" aria-live="polite">
            <img src="{{ asset('images/loader.gif') }}" alt="" width="24" height="24">
            <span>{{ __('Working') }}</span>
        </div>
    </div>
@endpersist

<noscript>
    <style>#page-preloader { display: none; }</style>
</noscript>

<script data-navigate-once>
    (function () {
        var overlay = document.getElementById('page-preloader');
        var badge = document.getElementById('livewire-activity');
        var started = false;

        var removeTimer = null;

        function showOverlay() {
            if (!overlay) { return; }
            clearTimeout(removeTimer);
            overlay.classList.remove('is-gone');
            overlay.classList.remove('is-done');
        }

        function hideOverlay() {
            if (!overlay) { return; }
            overlay.classList.add('is-done');

            // The fade is decoration. Take the overlay out of the page once it
            // has had time to run, so a transition that never fires cannot
            // leave a visitor staring at the loader.
            clearTimeout(removeTimer);
            removeTimer = setTimeout(function () {
                overlay.classList.add('is-gone');
            }, 400);
        }

        function start() {
            if (started) { return; }
            started = true;
            hideOverlay();

            document.addEventListener('livewire:navigate', showOverlay);
            document.addEventListener('livewire:navigated', hideOverlay);
        }

        if (document.readyState === 'complete') {
            start();
        } else {
            window.addEventListener('load', start, { once: true });
        }

        // A stalled image must never leave a visitor stranded behind the overlay.
        setTimeout(start, 6000);

        document.addEventListener('livewire:init', function () {
            var inFlight = 0;
            var revealTimer = null;
            var watchdog = null;

            function clearBadge() {
                inFlight = 0;
                clearTimeout(revealTimer);
                clearTimeout(watchdog);
                revealTimer = null;
                watchdog = null;
                badge && badge.classList.remove('is-active');
            }

            Livewire.hook('commit', function (payload) {
                inFlight++;

                if (revealTimer === null) {
                    revealTimer = setTimeout(function () {
                        badge && badge.classList.add('is-active');
                    }, 300);
                }

                // A request that never comes back must not strand the badge.
                clearTimeout(watchdog);
                watchdog = setTimeout(clearBadge, 15000);

                // Livewire 4 settles an ordinary round trip through respond().
                // succeed() and fail() are handed to the hook but are not
                // invoked for one, so relying on them leaves the badge stuck.
                payload.respond(function () {
                    if (--inFlight > 0) { return; }

                    clearBadge();
                });
            });
        });

    })();
</script>
