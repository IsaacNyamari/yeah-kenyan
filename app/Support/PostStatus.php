<?php

namespace App\Support;

/**
 * Where an article sits in review.
 *
 * Publication stays a separate question: an approved article still needs a
 * published_at date before the public site will show it.
 */
enum PostStatus: string
{
    case Draft = 'draft';

    case Pending = 'pending';

    case Approved = 'approved';

    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Pending => 'Awaiting review',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::Pending => 'amber',
            self::Approved => 'lime',
            self::Rejected => 'red',
        };
    }

    /**
     * Whether an author may still edit an article in this state.
     */
    public function isEditableByAuthor(): bool
    {
        return $this === self::Draft || $this === self::Rejected;
    }
}
