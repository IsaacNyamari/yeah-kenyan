<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Throwable;

/**
 * Pushes CMS-managed settings into the runtime config.
 *
 * Mail credentials and the Google Analytics property live in the database so
 * an administrator can change them from the dashboard, rather than needing SSH
 * access to edit .env — which nobody has on shared cPanel hosting.
 *
 * Anything not set in the database falls through to the config defaults, so a
 * fresh install (or a install whose database is unreachable) still boots.
 */
class SettingsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        try {
            $settings = Setting::allValues();
        } catch (Throwable) {
            // Never let a settings lookup stop the application from booting.
            return;
        }

        if ($settings === []) {
            return;
        }

        $this->applyGeneral($settings);
        $this->applyMail($settings);
        $this->applyAnalytics($settings);
    }

    /**
     * Site identity and SEO copy, read all over the public site.
     *
     * @param  array<string, string|null>  $settings
     */
    private function applyGeneral(array $settings): void
    {
        $map = [
            'site_name' => 'site.name',
            'site_slogan' => 'site.slogan',
            'site_timezone' => 'site.timezone',
            'meta_description' => 'site.meta.description',
            'meta_keywords' => 'site.meta.keywords',
            'contact_address' => 'site.contact.address',
            'contact_email' => 'site.contact.email',
            'contact_phone' => 'site.contact.phone',
            'social_facebook' => 'site.social.facebook',
            'social_instagram' => 'site.social.instagram',
            'social_youtube' => 'site.social.youtube',
        ];

        foreach ($map as $setting => $key) {
            if (filled($settings[$setting] ?? null)) {
                Config::set($key, $settings[$setting]);
            }
        }

        // Keeps the framework's own name (mail "from", auth screens) aligned.
        if (filled($settings['site_name'] ?? null)) {
            Config::set('app.name', $settings['site_name']);
        }
    }

    /**
     * @param  array<string, string|null>  $settings
     */
    private function applyMail(array $settings): void
    {
        if (blank($settings['mail_host'] ?? null)) {
            return;
        }

        $encryption = $settings['mail_encryption'] ?? 'tls';

        Config::set([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $settings['mail_host'],
            'mail.mailers.smtp.port' => (int) ($settings['mail_port'] ?? 587),
            'mail.mailers.smtp.username' => $settings['mail_username'] ?? null,
            'mail.mailers.smtp.password' => $settings['mail_password'] ?? null,
            // Symfony reads the scheme: "smtps" is implicit TLS (465), while
            // "smtp" negotiates STARTTLS (587).
            'mail.mailers.smtp.scheme' => $encryption === 'ssl' ? 'smtps' : 'smtp',
        ]);

        if (filled($settings['mail_from_address'] ?? null)) {
            Config::set('mail.from.address', $settings['mail_from_address']);
        }

        if (filled($settings['mail_from_name'] ?? null)) {
            Config::set('mail.from.name', $settings['mail_from_name']);
        }

        if (filled($settings['mail_enquiries_to'] ?? null)) {
            Config::set('mail.enquiries_to', $settings['mail_enquiries_to']);
        }
    }

    /**
     * @param  array<string, string|null>  $settings
     */
    private function applyAnalytics(array $settings): void
    {
        if (filled($settings['analytics_property_id'] ?? null)) {
            Config::set('analytics.property_id', $settings['analytics_property_id']);
        }

        // The service-account key is held as JSON in the database rather than
        // as a file, because a writable, non-public key file is awkward to
        // place safely on shared hosting. Spatie accepts an array here.
        if (filled($settings['analytics_credentials'] ?? null)) {
            $credentials = json_decode((string) $settings['analytics_credentials'], true);

            if (is_array($credentials)) {
                Config::set('analytics.service_account_credentials_json', $credentials);
            }
        }
    }
}
