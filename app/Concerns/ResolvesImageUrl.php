<?php

namespace App\Concerns;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Resolves an `image` column to a public URL.
 *
 * Images uploaded through the CMS are optimized onto the public disk and are
 * served from /storage. Everything migrated from the legacy PHP site was
 * copied straight into public/ instead, so those paths resolve with asset().
 */
trait ResolvesImageUrl
{
    /**
     * Directories that were carried over from the legacy site and live
     * directly under public/. This is a closed set — new uploads always go to
     * the storage disk.
     *
     * @var list<string>
     */
    private const LEGACY_PUBLIC_DIRECTORIES = ['uploads/', 'images/'];

    public function getImageUrlAttribute(): ?string
    {
        if (blank($this->image)) {
            return null;
        }

        return Str::startsWith($this->image, self::LEGACY_PUBLIC_DIRECTORIES)
            ? asset($this->image)
            : Storage::disk('public')->url($this->image);
    }
}
