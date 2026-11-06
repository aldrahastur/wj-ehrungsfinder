<?php

namespace App\Domain\Ehrungen;

enum RecipientGroup: string
{
    case Member = 'member';
    case Former = 'former';
    case External = 'external';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $g) => $g->value, self::cases());
    }
}
