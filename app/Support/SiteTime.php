<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * Converts a stored timestamp into the site's display timezone.
 *
 * Timestamps are written in UTC and converted only when shown, so changing the
 * timezone setting never alters records that already exist — see the note in
 * AppServiceProvider.
 *
 * This began as a Carbon macro, but a macro binds $this to the Carbon instance
 * at call time, which static analysis cannot follow. A plain function is fully
 * typed and reads no worse in a template.
 */
final class SiteTime
{
    public static function convert(?DateTimeInterface $date): ?CarbonImmutable
    {
        if ($date === null) {
            return null;
        }

        // config() only substitutes a default for a missing key, not a null one.
        return CarbonImmutable::instance($date)->setTimezone(config('site.timezone') ?: 'UTC');
    }
}
