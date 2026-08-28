<?php

namespace App\Services;

use App\Console\Commands\ReleaseCommand;
use Illuminate\Support\Facades\File;

/**
 * What version of the site is installed.
 *
 * The number lives in a VERSION file at the root rather than being read from a
 * git tag. The file travels with the code however it arrives — a pull, an FTP
 * upload, an extracted archive — so the site can always say what it is running,
 * including on a host where PHP cannot start git at all.
 *
 * Tags remain the record of a release; {@see ReleaseCommand}
 * writes both together so they cannot drift.
 */
class AppVersion
{
    private const FALLBACK = '0.0.0';

    private ?string $cached = null;

    /**
     * The installed version, e.g. "1.0.0".
     */
    public function current(): string
    {
        if ($this->cached !== null) {
            return $this->cached;
        }

        $path = base_path('VERSION');

        if (! File::exists($path)) {
            return $this->cached = self::FALLBACK;
        }

        $version = trim(File::get($path));

        return $this->cached = $this->isValid($version) ? $version : self::FALLBACK;
    }

    /**
     * The version with a leading v, as tags are written.
     */
    public function tag(): string
    {
        return 'v'.$this->current();
    }

    /**
     * Whether $other is newer than what is installed.
     */
    public function isBehind(string $other): bool
    {
        return $this->compare($other) > 0;
    }

    /**
     * Compare a version against the installed one.
     *
     * @return int 1 when $other is newer, -1 when older, 0 when the same or unreadable
     */
    public function compare(string $other): int
    {
        $other = $this->normalise($other);

        if (! $this->isValid($other)) {
            return 0;
        }

        return version_compare($other, $this->current());
    }

    /**
     * Strip the leading v a tag carries, so "v1.2.0" and "1.2.0" compare equal.
     */
    public function normalise(string $version): string
    {
        return ltrim(trim($version), 'vV');
    }

    public function isValid(string $version): bool
    {
        return preg_match('/^\d+\.\d+\.\d+$/', $this->normalise($version)) === 1;
    }

    /**
     * The next version for each kind of change, offered by the release command.
     *
     * @return array<string, string>
     */
    public function nextVersions(): array
    {
        [$major, $minor, $patch] = array_map('intval', explode('.', $this->current()));

        return [
            'patch' => sprintf('%d.%d.%d', $major, $minor, $patch + 1),
            'minor' => sprintf('%d.%d.0', $major, $minor + 1),
            'major' => sprintf('%d.0.0', $major + 1),
        ];
    }
}
