<?php

namespace App\Support;

/**
 * The abilities a role can carry.
 *
 * Spatie stores these as strings; naming them here keeps the spelling in one
 * place and makes an unknown ability a type error rather than a silent denial.
 */
enum Permission: string
{
    case ManageRoles = 'manage roles';

    case ManageSettings = 'manage settings';

    case ModeratePosts = 'moderate posts';

    case ManageNewsletters = 'manage newsletters';

    case ManageSubscribers = 'manage subscribers';

    case CreatePosts = 'create posts';

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_map(fn (self $permission): string => $permission->value, self::cases());
    }
}
