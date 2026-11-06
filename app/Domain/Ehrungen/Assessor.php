<?php

namespace App\Domain\Ehrungen;

/**
 * Leitet aus Regelgrundlage und Auswertung den Ergebniszustand einer Ehrung ab.
 */
final class Assessor
{
    public const REASON_NO_PUBLISHED_RULE = 'no_published_rule';

    public const REASON_PENDING_CONFIRMATION = 'pending_confirmation';

    public const REASON_CONFLICTING = 'conflicting';

    public const REASON_INCOMPLETE = 'incomplete';

    public const REASON_BLOCKED_PATH = 'blocked_path';

    public const REASON_RECIPIENT_UNCLEAR = 'recipient_unclear';

    public const REASON_PRIOR_UNCLEAR = 'prior_unclear';

    public function __construct(
        private readonly Evaluator $evaluator = new Evaluator,
    ) {}

    /**
     * @param  array<string, string>  $answers  bereits um abgeleitete Antworten ergänzt
     * @param  bool  $allowPendingConfirmation  Pilotbetrieb: recherchierte, noch unbestätigte Regeln auswerten
     */
    public function assess(HonorRule $rule, array $answers, RecipientGroup $group, bool $allowPendingConfirmation): Assessment
    {
        if ($rule->basis === RuleBasis::InfoOnly) {
            return new Assessment($rule, ResultState::Info);
        }

        if (! $rule->hasPublishedRule || $rule->criteria === null) {
            return new Assessment($rule, ResultState::Clarification, reason: self::REASON_NO_PUBLISHED_RULE);
        }

        $tree = $this->evaluator->evaluate($rule->criteria, $answers, $group);
        $raw = $tree->value;

        return match ($rule->basis) {
            RuleBasis::Conflicting => new Assessment($rule, ResultState::Clarification, $raw, $tree, self::REASON_CONFLICTING),

            // Fehlende Einzelregeln: Nur ein sicher nicht erfüllter Zweck führt zu „nicht passend“.
            RuleBasis::Incomplete => $raw === Truth::False
                ? new Assessment($rule, ResultState::NotSuitable, $raw, $tree)
                : new Assessment($rule, ResultState::Clarification, $raw, $tree, self::REASON_INCOMPLETE),

            RuleBasis::PendingConfirmation => $allowPendingConfirmation
                ? $this->fromTree($rule, $raw, $tree)
                : new Assessment($rule, ResultState::Clarification, $raw, $tree, self::REASON_PENDING_CONFIRMATION),

            RuleBasis::Confirmed => $this->fromTree($rule, $raw, $tree),
        };
    }

    private function fromTree(HonorRule $rule, Truth $raw, EvaluatedNode $tree): Assessment
    {
        $state = ResultState::fromTruth($raw);

        if ($state !== ResultState::Clarification) {
            return new Assessment($rule, $state, $raw, $tree);
        }

        [$reason, $detail] = $this->blockReason($tree) ?? [self::REASON_BLOCKED_PATH, null];

        return new Assessment($rule, $state, $raw, $tree, $reason, $detail);
    }

    /**
     * Ersten Grund für eine Blockade im Baum finden – nur in Zweigen, die das Ergebnis tragen.
     *
     * @return array{0: string, 1: mixed}|null
     */
    private function blockReason(EvaluatedNode $node): ?array
    {
        if ($node->value !== Truth::Blocked) {
            return null;
        }

        return match ($node->kind) {
            'blocked' => [self::REASON_BLOCKED_PATH, $node->data['reason'] ?? null],
            'recipient' => [self::REASON_RECIPIENT_UNCLEAR, $node->data['group']],
            'prior' => [self::REASON_PRIOR_UNCLEAR, $node->data['question']],
            default => array_reduce(
                $node->children,
                fn (?array $found, EvaluatedNode $child) => $found ?? $this->blockReason($child),
            ),
        };
    }
}
