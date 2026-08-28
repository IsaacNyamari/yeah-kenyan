<?php

namespace App\Support;

/**
 * The roles this site recognises.
 *
 * Registration only ever grants Author. Moderator and Admin are handed out
 * from the roles screen, because both can act on other people's work.
 */
enum UserRole: string
{
    case Admin = 'admin';

    case Moderator = 'moderator';

    case Author = 'author';

    /**
     * The role a newly registered account receives.
     */
    public const DEFAULT = self::Author;

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Moderator => 'Moderator',
            self::Author => 'Author',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Admin => 'Full access, including settings, roles, subscribers and newsletters.',
            self::Moderator => 'Reviews submitted articles and nothing else. Cannot edit site content.',
            self::Author => 'Writes articles and submits them for review. Sees only their own.',
        };
    }

    /**
     * Flux badge colour, so a role reads the same everywhere it is shown.
     */
    public function badgeColor(): string
    {
        return match ($this) {
            self::Admin => 'red',
            self::Moderator => 'amber',
            self::Author => 'zinc',
        };
    }

    /**
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Admin => Permission::cases(),
            // Review only. A moderator judges other people's work, so giving
            // them the content screens would let them edit the very articles
            // they are meant to be checking.
            self::Moderator => [Permission::ModeratePosts],
            self::Author => [Permission::ManageNews],
        };
    }

    /**
     * @return list<string>
     */
    public function permissionNames(): array
    {
        return array_map(fn (Permission $permission): string => $permission->value, $this->permissions());
    }

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_map(fn (self $role): string => $role->value, self::cases());
    }
}
