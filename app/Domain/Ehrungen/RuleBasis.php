<?php

namespace App\Domain\Ehrungen;

/**
 * Fachlicher Stand der Regelgrundlage einer Regelversion.
 */
enum RuleBasis: string
{
    /** Schriftlich vom Verband bestätigt. */
    case Confirmed = 'confirmed';

    /** Aus öffentlichen Quellen recherchiert, Bestätigung steht aus. */
    case PendingConfirmation = 'pending_confirmation';

    /** Quellen widersprechen sich. */
    case Conflicting = 'conflicting';

    /** Existenz und Zweck belegt, verbindliche Einzelregeln fehlen. */
    case Incomplete = 'incomplete';

    /** Nur Informationskarte, keine Eignungsprüfung. */
    case InfoOnly = 'info_only';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $b) => $b->value, self::cases());
    }
}
