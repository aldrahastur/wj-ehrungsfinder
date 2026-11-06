<?php

namespace App\Domain\Ehrungen;

enum Answer: string
{
    case Yes = 'yes';
    case No = 'no';
    case Unknown = 'unknown';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $a) => $a->value, self::cases());
    }
}
