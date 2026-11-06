<?php

namespace App\Support;

final class Localized
{
    public const FALLBACK = 'de';

    /**
     * Text in der aktuellen Sprache, sonst Deutsch, sonst der erste vorhandene.
     *
     * @param  array<string, string>|string|null  $value
     */
    public static function pick(array|string|null $value, ?string $locale = null): string
    {
        if ($value === null) {
            return '';
        }

        if (is_string($value)) {
            return $value;
        }

        $locale ??= app()->getLocale();

        return $value[$locale] ?? $value[self::FALLBACK] ?? (string) (array_values($value)[0] ?? '');
    }

    /**
     * @return list<string>
     */
    public static function locales(): array
    {
        return config('ehrungsfinder.locales', ['de', 'en']);
    }
}
