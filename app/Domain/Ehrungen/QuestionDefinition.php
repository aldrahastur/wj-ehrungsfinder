<?php

namespace App\Domain\Ehrungen;

final readonly class QuestionDefinition
{
    /**
     * Ableitungen in $impliedBy, z. B. „unter 2 Jahren“ ⇒ „nicht 5 Jahre“.
     *
     * @param  list<array{question: string, answer: string, implies: string}>  $impliedBy
     */
    public function __construct(
        public string $code,
        public ?string $region = null,
        public array $impliedBy = [],
    ) {}

    public function appliesTo(?string $region): bool
    {
        return $this->region === null || $this->region === $region;
    }
}
