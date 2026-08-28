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
        return self::urlFor($this->image);
    }

    /**
     * Resolve a stored path to a URL without needing a model instance —
     * used when an editor has picked a gallery path but not yet saved it.
     */
    public static function urlFor(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return Str::startsWith($path, self::LEGACY_PUBLIC_DIRECTORIES)
            ? asset($path)
            : Storage::disk('public')->url($path);
    }
}
