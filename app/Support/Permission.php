<?php

namespace App\Support;

/**
 * One permission per area of the dashboard.
 *
 * Kept at that granularity on purpose: an administrator hands these out
 * individually, so "may review submissions" has to be grantable without also
 * granting "may edit the gallery". Every admin route is gated on one of these
 * and every sidebar entry is shown by one, so a permission an account does not
 * hold is neither reachable nor advertised.
 */
enum Permission: string
{
    case ManageNews = 'manage news';

    case ModeratePosts = 'moderate posts';

    case ManageHomepage = 'manage homepage';

    case ManageGallery = 'manage gallery';

    case ManageServices = 'manage services';

    case ManageClasses = 'manage classes';

    case ManageTestimonials = 'manage testimonials';

    case ManageMessages = 'manage messages';

    case ManageContact = 'manage contact';

    case ManageNewsletters = 'manage newsletters';

    case ManageSubscribers = 'manage subscribers';

    case ViewAnalytics = 'view analytics';

    case ManageSettings = 'manage settings';

    case ManageRoles = 'manage roles';

    public function label(): string
    {
        return match ($this) {
            self::ManageNews => 'Write articles',
            self::ModeratePosts => 'Review submissions',
            self::ManageHomepage => 'Edit the homepage hero',
            self::ManageGallery => 'Manage the gallery',
            self::ManageServices => 'Manage services',
            self::ManageClasses => 'Manage online classes',
            self::ManageTestimonials => 'Manage testimonials',
            self::ManageMessages => 'Read contact messages',
            self::ManageContact => 'Edit contact details',
            self::ManageNewsletters => 'Build and send newsletters',
            self::ManageSubscribers => 'Manage subscribers',
            self::ViewAnalytics => 'View analytics',
            self::ManageSettings => 'Change site settings',
            self::ManageRoles => 'Assign roles and permissions',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ManageNews => 'Write and submit articles. Authors only ever see their own.',
            self::ModeratePosts => 'Approve or send back articles other people submitted.',
            self::ManageHomepage => 'Change the rotating banner and the tiles beside it.',
            self::ManageGallery => 'Upload and remove gallery images.',
            self::ManageServices => 'Edit the service pages.',
            self::ManageClasses => 'Edit the online class pages.',
            self::ManageTestimonials => 'Edit the testimonials carousel.',
            self::ManageMessages => 'Read enquiries sent through the contact form.',
            self::ManageContact => 'Edit the address, phone and social links.',
            self::ManageNewsletters => 'Write newsletters, edit templates and send issues.',
            self::ManageSubscribers => 'See, add and remove mailing list subscribers.',
            self::ViewAnalytics => 'See site traffic figures.',
            self::ManageSettings => 'Mail credentials, API keys and site-wide switches.',
            self::ManageRoles => 'Decide what everyone else may do.',
        };
    }

    /**
     * The heading this permission sits under on the roles screen.
     */
    public function group(): string
    {
        return match ($this) {
            self::ManageNews, self::ModeratePosts => 'Newsroom',
            self::ManageHomepage, self::ManageGallery, self::ManageServices, self::ManageClasses,
            self::ManageTestimonials, self::ManageContact => 'Site content',
            self::ManageMessages, self::ManageNewsletters, self::ManageSubscribers => 'Audience',
            self::ViewAnalytics, self::ManageSettings, self::ManageRoles => 'Administration',
        };
    }

    /**
     * Permissions that hand over control of the site itself rather than of a
     * single area. Shown with a warning on the roles screen.
     */
    public function isPrivileged(): bool
    {
        return in_array($this, [self::ManageSettings, self::ManageRoles], true);
    }

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_map(fn (self $permission): string => $permission->value, self::cases());
    }

    /**
     * @return array<string, list<self>>
     */
    public static function grouped(): array
    {
        $grouped = [];

        foreach (self::cases() as $permission) {
            $grouped[$permission->group()][] = $permission;
        }

        return $grouped;
    }
}
