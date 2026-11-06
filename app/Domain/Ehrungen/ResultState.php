<?php

namespace App\Domain\Ehrungen;

enum ResultState: string
{
    /** Bestätigte Regeln nach Angaben erfüllt; Entscheidung des Gremiums steht aus. */
    case Suitable = 'suitable';

    /** Angaben oder Nachweise fehlen. */
    case Possible = 'possible';

    /** Konkrete Pflichtbedingung nicht erfüllt. */
    case NotSuitable = 'not_suitable';

    /** Regelgrundlage fehlt oder widerspricht sich. */
    case Clarification = 'clarification';

    /** Nur Informationskarte, keine automatische Prüfung. */
    case Info = 'info';

    public static function fromTruth(Truth $truth): self
    {
        return match ($truth) {
            Truth::True => self::Suitable,
            Truth::Unknown => self::Possible,
            Truth::False => self::NotSuitable,
            Truth::Blocked => self::Clarification,
        };
    }

    public function sortOrder(): int
    {
        return match ($this) {
            self::Suitable => 0,
            self::Possible => 1,
            self::Clarification => 2,
            self::NotSuitable => 3,
            self::Info => 4,
        };
    }
}
