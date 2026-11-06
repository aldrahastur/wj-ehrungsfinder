<?php

namespace App\Domain\Ehrungen;

/**
 * Wahrheitswert eines Kriteriums.
 *
 * Neben erfüllt/nicht erfüllt gibt es „unbekannt“ (Angabe fehlt oder „Weiß ich nicht“)
 * und „blockiert“ (Regelgrundlage fehlt oder ist gesperrt → fachliche Klärung).
 */
enum Truth: string
{
    case True = 'T';
    case False = 'F';
    case Unknown = 'U';
    case Blocked = 'B';

    /**
     * UND: Ein sicher falsches Pflichtkriterium schließt aus, sonst halten
     * Blockade bzw. Unbekannt das Ergebnis offen.
     *
     * @param  list<Truth>  $values
     */
    public static function all(array $values): self
    {
        foreach ([self::False, self::Blocked, self::Unknown] as $dominant) {
            if (in_array($dominant, $values, true)) {
                return $dominant;
            }
        }

        return self::True;
    }

    /**
     * ODER: Ein sicher erfüllter Zugang genügt.
     *
     * @param  list<Truth>  $values
     */
    public static function any(array $values): self
    {
        foreach ([self::True, self::Blocked, self::Unknown] as $dominant) {
            if (in_array($dominant, $values, true)) {
                return $dominant;
            }
        }

        return self::False;
    }

    public function negate(): self
    {
        return match ($this) {
            self::True => self::False,
            self::False => self::True,
            default => $this,
        };
    }

    public static function fromAnswer(?string $answer): self
    {
        return match ($answer) {
            Answer::Yes->value => self::True,
            Answer::No->value => self::False,
            default => self::Unknown,
        };
    }
}
