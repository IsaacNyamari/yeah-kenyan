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
