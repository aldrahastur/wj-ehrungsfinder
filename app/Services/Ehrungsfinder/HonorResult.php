<?php

namespace App\Services\Ehrungsfinder;

use App\Domain\Ehrungen\Assessment;
use App\Models\Honor;
use App\Models\RuleVersion;

final readonly class HonorResult
{
    public function __construct(
        public Honor $honor,
        public Assessment $assessment,
        public ?RuleVersion $ruleVersion,
    ) {}

    /**
     * Kompakte, anonyme Form für die Speicherung im Ergebnis.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'honor' => $this->honor->code,
            'rule_version_id' => $this->ruleVersion?->id,
            'rule_version' => $this->ruleVersion?->version,
            'basis' => $this->ruleVersion?->basis->value,
            'state' => $this->assessment->state->value,
            'raw' => $this->assessment->raw?->value,
            'reason' => $this->assessment->reason,
            'criteria' => $this->assessment->criteriaSummary(),
        ];
    }
}
