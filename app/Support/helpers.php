<?php

use App\Support\SiteTime;
use Carbon\CarbonImmutable;

if (! function_exists('site_time')) {
    /**
     * Render a stored (UTC) timestamp in the site's display timezone.
     */
    function site_time(?DateTimeInterface $date): ?CarbonImmutable
    {
        return SiteTime::convert($date);
    }
}

/*
 * Compatibility shim for hosts that disable getmypid().
 *
 * Shared hosting commonly lists getmypid() in disable_functions as part of a
 * blanket policy, which breaks Google's API client: it calls the function
 * unqualified from inside a namespace, so the lookup falls through to global
 * and fails with "undefined function".
 *
 * disable_functions removes the function from PHP's function table rather than
 * reserving the name, so a userland definition is picked up by that fallback.
 *
 * Every use in the Google stack is a log identifier or a cache-key component
 * (request ids, log event process ids). None reads process state, so returning
 * a synthetic value satisfies the library without granting anything the host
 * was withholding. A value fixed for the life of the request mirrors what a
 * real pid provides: stable within a process, different between them.
 */
if (! function_exists('getmypid')) {
    function getmypid(): int
    {
        static $pid = null;

        return $pid ??= random_int(10000, 99999);
    }
}
