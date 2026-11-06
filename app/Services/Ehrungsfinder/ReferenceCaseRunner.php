<?php

namespace App\Services\Ehrungsfinder;

use App\Domain\Ehrungen\RecipientGroup;
use App\Models\ReferenceCase;
use App\Models\RuleVersion;
use Illuminate\Support\Collection;

/**
 * Prüft die Referenzfälle gegen veröffentlichte Regeln oder gegen einen Entwurf.
 */
class ReferenceCaseRunner
{
    public function __construct(private readonly Finder $finder) {}

    /**
     * @param  array<int, RuleVersion>  $overrides
     * @return Collection<int, array{case: ReferenceCase, passed: bool, mismatches: array<string, array{expected: ?string, actual: ?string}>}>
     */
    public function run(array $overrides = [], ?Collection $cases = null): Collection
    {
        $cases ??= ReferenceCase::query()->orderBy('id')->get();

        return $cases->map(function (ReferenceCase $case) use ($overrides) {
            $actual = $this->finder
                ->assess($case->region, RecipientGroup::from($case->recipient_group), $case->answers, $overrides, pilotMode: true)
                ->mapWithKeys(fn (HonorResult $r) => [$r->honor->code => $r->assessment->state->value])
                ->all();

            $mismatches = [];
            foreach (array_unique([...array_keys($case->expected), ...array_keys($actual)]) as $code) {
                if (($case->expected[$code] ?? null) !== ($actual[$code] ?? null)) {
                    $mismatches[$code] = ['expected' => $case->expected[$code] ?? null, 'actual' => $actual[$code] ?? null];
                }
            }

            return ['case' => $case, 'passed' => $mismatches === [], 'mismatches' => $mismatches];
        });
    }
}
