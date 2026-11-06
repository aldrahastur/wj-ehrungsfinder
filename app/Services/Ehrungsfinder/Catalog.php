<?php

namespace App\Services\Ehrungsfinder;

use App\Domain\Ehrungen\HonorRule;
use App\Domain\Ehrungen\QuestionDefinition;
use App\Models\Honor;
use App\Models\Question;
use App\Models\RuleVersion;
use Illuminate\Support\Collection;

/**
 * Lädt Ehrungen, veröffentlichte Regelversionen und Fragen aus der Datenbank.
 */
class Catalog
{
    /** @var Collection<int, Honor>|null */
    private ?Collection $honors = null;

    /** @var Collection<int, Question>|null */
    private ?Collection $questions = null;

    /**
     * @return Collection<int, Honor>
     */
    public function honors(): Collection
    {
        return $this->honors ??= Honor::query()
            ->active()
            ->ordered()
            ->with(['organisation', 'procedure', 'publishedRule.sources'])
            ->get();
    }

    /**
     * @return Collection<int, Honor>
     */
    public function honorsFor(?string $region): Collection
    {
        return $this->honors()->filter(fn (Honor $h) => $h->region === null || $h->region === $region)->values();
    }

    /**
     * @return Collection<int, Question>
     */
    public function questions(): Collection
    {
        return $this->questions ??= Question::query()->active()->ordered()->get()->keyBy('code');
    }

    public function question(string $code): ?Question
    {
        return $this->questions()->get($code);
    }

    /**
     * @param  array<int, RuleVersion>  $overrides  honor_id → Regelversion (z. B. Entwurf zum Testen)
     * @return list<HonorRule>
     */
    public function rules(array $overrides = []): array
    {
        return $this->honors()
            ->map(fn (Honor $h) => $h->toRule($overrides[$h->id] ?? null))
            ->values()
            ->all();
    }

    /**
     * @return list<QuestionDefinition>
     */
    public function questionDefinitions(): array
    {
        return $this->questions()->map(fn (Question $q) => $q->toDefinition())->values()->all();
    }

    public function flush(): void
    {
        $this->honors = null;
        $this->questions = null;
    }
}
