<?php

namespace Tests\Support;

use App\Domain\Ehrungen\HonorRule;
use App\Domain\Ehrungen\QuestionDefinition;
use App\Domain\Ehrungen\RuleBasis;

/**
 * Liest den Startkatalog ohne Datenbank, damit die Regel-Engine isoliert getestet werden kann.
 */
final class CatalogFixture
{
    /**
     * @return array<string, mixed>
     */
    public static function data(): array
    {
        return require __DIR__.'/../../database/data/catalog.php';
    }

    /**
     * @return list<HonorRule>
     */
    public static function rules(): array
    {
        return array_map(fn (array $h) => new HonorRule(
            honorCode: $h['code'],
            criteria: $h['rule']['criteria'],
            basis: RuleBasis::from($h['rule']['basis']),
            region: $h['region'],
        ), self::data()['honors']);
    }

    /**
     * @return list<QuestionDefinition>
     */
    public static function questions(): array
    {
        return array_map(fn (array $q) => new QuestionDefinition(
            code: $q['code'],
            region: $q['region'] ?? null,
            impliedBy: $q['implied_by'] ?? [],
        ), self::data()['questions']);
    }

    /**
     * @return list<string>
     */
    public static function questionCodes(): array
    {
        return array_column(self::data()['questions'], 'code');
    }
}
