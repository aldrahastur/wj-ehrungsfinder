<?php

namespace App\Domain\Ehrungen;

final readonly class Assessment
{
    /**
     * @param  string|null  $reason  Code für die Begründung einer Klärung (siehe Assessor::REASON_*)
     */
    public function __construct(
        public HonorRule $rule,
        public ResultState $state,
        public ?Truth $raw = null,
        public ?EvaluatedNode $tree = null,
        public ?string $reason = null,
        public mixed $reasonDetail = null,
    ) {}

    /**
     * @return array<string, list<string>>
     */
    public function criteriaSummary(): array
    {
        $summary = ['met' => [], 'open' => [], 'failed' => [], 'blocked' => []];

        foreach ($this->tree?->leaves() ?? [] as $leaf) {
            $key = $leaf->data['question'] ?? $leaf->kind;
            match ($leaf->value) {
                Truth::True => $summary['met'][] = $key,
                Truth::Unknown => $summary['open'][] = $key,
                Truth::False => $summary['failed'][] = $key,
                Truth::Blocked => $summary['blocked'][] = $key,
            };
        }

        return $summary;
    }
}
