<?php

namespace App\Support;

/**
 * The two halves of the homepage hero.
 *
 * They share a table and differ only in how they are shown: a slide rotates in
 * the large panel on the left, a tile sits in the fixed grid beside it.
 */
enum HeroPanelKind: string
{
    case Slide = 'slide';

    case Tile = 'tile';

    public function label(): string
    {
        return match ($this) {
            self::Slide => 'Rotating banner',
            self::Tile => 'Side tile',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Slide => 'The large panel on the left. Slides cycle every six seconds.',
            self::Tile => 'The smaller panels on the right, shown all at once.',
        };
    }
}
