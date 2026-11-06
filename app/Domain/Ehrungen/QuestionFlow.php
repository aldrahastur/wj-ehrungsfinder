<?php

namespace App\Domain\Ehrungen;

/**
 * Bestimmt die nächste sinnvolle Frage.
 *
 * Eine Frage wird nur gestellt, wenn „Ja“ und „Nein“ bei mindestens einer
 * anwendbaren Ehrung zu einem unterschiedlichen Rohergebnis führen. Dadurch
 * endet z. B. der Senatorenpfad, sobald „kein internationales Engagement“
 * feststeht, ohne andere Pfade zu beeinflussen.
 */
final class QuestionFlow
{
    /**
     * @param  list<QuestionDefinition>  $questions  in Reihenfolge der Darstellung
     * @param  list<HonorRule>  $rules
     */
    public function __construct(
        private readonly array $questions,
        private readonly array $rules,
        private readonly ?string $region,
        private readonly RecipientGroup $group,
        private readonly Evaluator $evaluator = new Evaluator,
    ) {}

    /**
     * Antworten um abgeleitete Antworten ergänzen (nur für nicht beantwortete Fragen).
     *
     * @param  array<string, string>  $answers
     * @return array<string, string>
     */
    public function effectiveAnswers(array $answers): array
    {
        $effective = $answers;

        foreach ($this->questions as $question) {
            if (isset($effective[$question->code])) {
                continue;
            }

            foreach ($question->impliedBy as $implication) {
                if (($effective[$implication['question']] ?? null) === $implication['answer']) {
                    $effective[$question->code] = $implication['implies'];
                    break;
                }
            }
        }

        return $effective;
    }

    /**
     * @param  array<string, string>  $answers
     */
    public function isRelevant(QuestionDefinition $question, array $answers): bool
    {
        if (! $question->appliesTo($this->region)) {
            return false;
        }

        $effective = $this->effectiveAnswers($answers);
        unset($effective[$question->code]);

        foreach ($this->applicableRules() as $rule) {
            $codes = Evaluator::questionCodes($rule->criteria);

            if (! in_array($question->code, $codes, true)) {
                continue;
            }

            // Noch offene andere Fragen dieser Ehrung durchspielen: Die Frage ist relevant,
            // wenn es eine mögliche Antwortkombination gibt, bei der Ja und Nein zu
            // unterschiedlichen Ergebnissen führen.
            $others = array_values(array_filter($codes, fn (string $c) => $c !== $question->code && ! isset($effective[$c])));

            if ($this->differsForSomeCompletion($rule, $effective, $question->code, $others)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, string>  $answers
     * @param  list<string>  $others
     */
    private function differsForSomeCompletion(HonorRule $rule, array $answers, string $code, array $others): bool
    {
        $combinations = 1 << count($others);

        for ($mask = 0; $mask < $combinations; $mask++) {
            $scenario = $answers;
            foreach ($others as $i => $other) {
                $scenario[$other] = ($mask >> $i) & 1 ? Answer::Yes->value : Answer::No->value;
            }

            $yes = $this->evaluator->evaluate($rule->criteria, $this->effectiveAnswers([...$scenario, $code => Answer::Yes->value]), $this->group)->value;
            $no = $this->evaluator->evaluate($rule->criteria, $this->effectiveAnswers([...$scenario, $code => Answer::No->value]), $this->group)->value;

            if ($yes !== $no) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, string>  $answers
     */
    public function next(array $answers): ?QuestionDefinition
    {
        return $this->open($answers)[0] ?? null;
    }

    /**
     * Noch offene, relevante Fragen in Reihenfolge.
     *
     * @param  array<string, string>  $answers
     * @return list<QuestionDefinition>
     */
    public function open(array $answers): array
    {
        $effective = $this->effectiveAnswers($answers);

        return array_values(array_filter(
            $this->questions,
            fn (QuestionDefinition $q) => ! isset($effective[$q->code]) && $this->isRelevant($q, $answers),
        ));
    }

    /**
     * Ehrungen, für die eine Frage ausgewertet wird.
     *
     * @return list<string>
     */
    public function honorsUsing(string $questionCode): array
    {
        return array_values(array_map(
            fn (HonorRule $r) => $r->honorCode,
            array_filter($this->applicableRules(), fn (HonorRule $r) => in_array($questionCode, Evaluator::questionCodes($r->criteria), true)),
        ));
    }

    /**
     * @return list<HonorRule>
     */
    private function applicableRules(): array
    {
        return array_values(array_filter(
            $this->rules,
            fn (HonorRule $r) => $r->isEvaluable() && $r->appliesTo($this->region),
        ));
    }
}
