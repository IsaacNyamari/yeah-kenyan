<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Moves the service and class content out of config/pages.php and into the
 * pages table so it becomes editable through the CMS.
 *
 * The config file remains the source of truth for a first install; once seeded,
 * the database wins. Idempotent, so re-running restores any deleted page.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPages();
        $this->seedSettings();
    }

    private function seedPages(): void
    {
        $order = 0;

        foreach (config('pages') as $slug => $page) {
            Page::updateOrCreate(
                ['slug' => $slug],
                [
                    'type' => $page['type'],
                    'nav' => $page['nav'],
                    'title' => $page['title'],
                    'heading' => $page['heading'],
                    'cta' => $page['cta'],
                    'image' => $page['image'],
                    'intro' => $page['intro'],
                    'sections' => $page['sections'],
                    'footnotes' => $page['footnotes'] ?? null,
                    'sort_order' => $order++,
                    'is_published' => true,
                ],
            );
        }
    }

    private function seedSettings(): void
    {
        Setting::putMany([
            'contact_address' => config('site.contact.address'),
            'contact_email' => config('site.contact.email'),
            'contact_phone' => config('site.contact.phone'),
            'social_facebook' => config('site.social.facebook'),
            'social_instagram' => config('site.social.instagram'),
            'social_youtube' => config('site.social.youtube'),
            'contact_heading' => 'Contact Us For Any Queries',
            'contact_intro' => 'Tell us about your event and we will get back to you with a tailored package.',
            'contact_success_message' => 'Thank you for reaching out. We will get back to you shortly.',
            'contact_button_label' => 'Send Message',
            'contact_show_map' => '1',

            // Live chat, carried over from the legacy site where it sat commented out.
            'tawk_enabled' => '1',
            'tawk_property_id' => '67c0195edbf28e190997f139',
            'tawk_widget_id' => '1il366770',

            // General
            'site_name' => config('site.name'),
            'site_slogan' => config('site.slogan'),
            'site_timezone' => config('site.timezone', 'Africa/Nairobi'),
            'meta_description' => config('site.meta.description'),
            'meta_keywords' => config('site.meta.keywords'),
        ]);

        $this->seedMailFromEnvironment();
    }

    /**
     * Lift the working mail settings out of .env and into the database, so the
     * CMS becomes the place they are edited from here on.
     *
     * Only fills blanks: once an administrator saves through the dashboard,
     * re-running the seeder must not overwrite their values.
     */
    private function seedMailFromEnvironment(): void
    {
        $defaults = [
            'mail_host' => config('mail.mailers.smtp.host'),
            'mail_port' => (string) config('mail.mailers.smtp.port'),
            'mail_username' => config('mail.mailers.smtp.username'),
            'mail_password' => config('mail.mailers.smtp.password'),
            'mail_encryption' => 'tls',
            'mail_from_address' => config('mail.from.address'),
            'mail_from_name' => config('mail.from.name'),
            'mail_enquiries_to' => config('mail.enquiries_to'),
        ];

        $missing = collect($defaults)
            ->filter(fn ($value): bool => filled($value))
            ->reject(fn ($value, $key): bool => filled(Setting::get($key)))
            ->all();

        if ($missing !== []) {
            Setting::putMany($missing);
        }
    }
}
