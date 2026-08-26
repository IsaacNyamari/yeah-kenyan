<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Key/value store for everything the CMS lets an administrator change:
 * contact details, mail credentials, analytics keys, integrations.
 *
 * Values are cached because they are read on nearly every request, and
 * credentials are encrypted at rest so a database dump does not hand over the
 * mail password or the Google service-account key.
 */
class Setting extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $fillable = ['key', 'value', 'is_encrypted'];

    private const CACHE_KEY = 'site.settings';

    /**
     * Keys whose values are encrypted at rest.
     *
     * @var list<string>
     */
    public const SECRETS = [
        'mail_password',
        'analytics_credentials',
        'nyt_api_key',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_encrypted' => 'boolean'];
    }

    /**
     * Every non-secret setting, keyed by name.
     *
     * Secrets are deliberately excluded. The cache store is the database, so
     * caching decrypted credentials here would write them back to disk in
     * plaintext and undo the encryption applied on save. They are read
     * straight from the row instead — see {@see secret()}.
     *
     * @return array<string, string|null>
     */
    public static function allValues(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            // Boot-time callers (the config override provider) can run before
            // migrations exist, so never assume the table is there.
            if (! self::tableExists()) {
                return [];
            }

            return self::query()
                ->whereNotIn('key', self::SECRETS)
                ->get(['key', 'value', 'is_encrypted'])
                ->mapWithKeys(fn (self $setting): array => [$setting->key => $setting->decoded()])
                ->all();
        });
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = in_array($key, self::SECRETS, true)
            ? self::secret($key)
            : (self::allValues()[$key] ?? null);

        return ($value === null || $value === '') ? $default : $value;
    }

    /**
     * Read and decrypt one secret, never touching the cache.
     */
    private static function secret(string $key): ?string
    {
        if (! self::tableExists()) {
            return null;
        }

        return self::query()->find($key)?->decoded();
    }

    public static function boolean(string $key, bool $default = false): bool
    {
        $value = self::get($key);

        return $value === null ? $default : in_array($value, ['1', 'true', 'on', 'yes'], true);
    }

    /**
     * Persist a batch of settings, encrypting any that are secrets.
     *
     * @param  array<string, string|bool|int|null>  $values
     */
    public static function putMany(array $values): void
    {
        foreach ($values as $key => $value) {
            $encrypt = in_array($key, self::SECRETS, true) && filled($value);

            $value = match (true) {
                is_bool($value) => $value ? '1' : '0',
                $value === null => null,
                default => (string) $value,
            };

            self::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $encrypt ? Crypt::encryptString($value) : $value,
                    'is_encrypted' => $encrypt,
                ],
            );
        }

        self::flush();
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * The stored value, decrypted when necessary.
     */
    private function decoded(): ?string
    {
        if (! $this->is_encrypted || blank($this->value)) {
            return $this->value;
        }

        try {
            return Crypt::decryptString($this->value);
        } catch (DecryptException) {
            // A rotated APP_KEY makes old ciphertext unreadable. Returning null
            // degrades to "unconfigured" rather than breaking every request.
            return null;
        }
    }

    private static function tableExists(): bool
    {
        try {
            return Schema::hasTable('settings');
        } catch (Throwable) {
            return false;
        }
    }
}
