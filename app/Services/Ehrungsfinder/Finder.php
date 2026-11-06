<?php

namespace App\Services\Ehrungsfinder;

use App\Domain\Ehrungen\Assessor;
use App\Domain\Ehrungen\QuestionFlow;
use App\Domain\Ehrungen\RecipientGroup;
use App\Models\Honor;
use App\Models\Question;
use App\Models\RuleVersion;
use Illuminate\Support\Collection;

/**
 * Verbindet Katalog und Regel-Engine für eine konkrete Vorprüfung.
 */
class Finder
{
    public function __construct(
        private readonly Catalog $catalog,
        private readonly Assessor $assessor = new Assessor,
    ) {}

    /**
     * @param  array<int, RuleVersion>  $overrides
     */
    public function flow(?string $region, RecipientGroup $group, array $overrides = []): QuestionFlow
    {
        return new QuestionFlow($this->catalog->questionDefinitions(), $this->catalog->rules($overrides), $region, $group);
    }

    /**
     * @param  array<string, string>  $answers
     */
    public function nextQuestion(?string $region, RecipientGroup $group, array $answers): ?Question
    {
        $next = $this->flow($region, $group)->next($answers);

        return $next ? $this->catalog->question($next->code) : null;
    }

    /**
     * @param  array<string, string>  $answers
     */
    public function remainingQuestions(?string $region, RecipientGroup $group, array $answers): int
    {
        return count($this->flow($region, $group)->open($answers));
    }

    /**
     * @return list<string>
     */
    public function honorsUsing(string $questionCode, ?string $region, RecipientGroup $group): array
    {
        return $this->flow($region, $group)->honorsUsing($questionCode);
    }

    /**
     * Alle anwendbaren Ehrungen auswerten. Jede Ehrung wird unabhängig geprüft;
     * eine Rangfolge zwischen Ebenen wird nicht gebildet.
     *
     * @param  array<string, string>  $answers
     * @param  array<int, RuleVersion>  $overrides
     * @return Collection<int, HonorResult>
     */
    public function assess(?string $region, RecipientGroup $group, array $answers, array $overrides = [], ?bool $pilotMode = null): Collection
    {
        $pilotMode ??= (bool) config('ehrungsfinder.pilot_mode');
        $effective = $this->flow($region, $group, $overrides)->effectiveAnswers($answers);

        return $this->catalog->honorsFor($region)
            ->map(function (Honor $honor) use ($overrides, $effective, $group, $pilotMode) {
                $version = $overrides[$honor->id] ?? $honor->publishedRule;

                return new HonorResult(
                    $honor,
                    $this->assessor->assess($honor->toRule($version), $effective, $group, $pilotMode),
                    $version,
                );
            })
            ->sortBy(fn (HonorResult $r) => [$r->assessment->state->sortOrder(), $r->honor->sort])
            ->values();
    }

    /**
     * @param  array<string, string>  $answers
     * @return array<string, string>
     */
    public function effectiveAnswers(?string $region, RecipientGroup $group, array $answers): array
    {
        return $this->flow($region, $group)->effectiveAnswers($answers);
    }
}
