<?php

namespace App\Domain\Ehrungen;

/**
 * Für die Auswertung benötigte Sicht auf eine Ehrung und ihre gültige Regelversion.
 */
final readonly class HonorRule
{
    /**
     * @param  array<string, mixed>|null  $criteria  null, wenn keine veröffentlichte Regelversion existiert
     */
    public function __construct(
        public string $honorCode,
        public ?array $criteria,
        public RuleBasis $basis,
        public ?string $region = null,
        public bool $hasPublishedRule = true,
        public ?int $ruleVersionId = null,
    ) {}

    public function appliesTo(?string $region): bool
    {
        return $this->region === null || $this->region === $region;
    }

    public function isEvaluable(): bool
    {
        return $this->basis !== RuleBasis::InfoOnly && $this->criteria !== null;
    }
}
