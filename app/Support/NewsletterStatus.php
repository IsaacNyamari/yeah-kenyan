<?php

namespace App\Support;

enum NewsletterStatus: string
{
    case Draft = 'draft';

    case Sending = 'sending';

    case Sent = 'sent';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sending => 'Sending',
            self::Sent => 'Sent',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::Sending => 'amber',
            self::Sent => 'lime',
        };
    }
}
