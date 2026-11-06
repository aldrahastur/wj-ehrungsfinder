<?php

namespace App\Models\Concerns;

use App\Support\Localized;

/**
 * Mehrsprachige JSON-Felder ({"de": "...", "en": "..."}) lesen.
 */
trait HasTranslations
{
    public function tr(string $attribute, ?string $locale = null): string
    {
        return Localized::pick($this->getAttribute($attribute), $locale);
    }
}
