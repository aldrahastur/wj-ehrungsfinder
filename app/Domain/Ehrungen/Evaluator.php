<?php

namespace App\Domain\Ehrungen;

use InvalidArgumentException;

/**
 * Deterministische Auswertung eines Kriterienausdrucks.
 *
 * Knotentypen (JSON):
 *  {"all": [...], "label": {...}}                       UND
 *  {"any": [...], "label": {...}}                       ODER
 *  {"not": {...}}                                       NICHT
 *  {"question": "q_code"}                               Ja/Nein-Frage
 *  {"recipient": {"member": "T", "former": "B", ...}}   Wer geehrt werden kann
 *  {"prior": "q_code", "mode": "exclude"|"unclear"}     Umgang mit Vorehrung derselben Ehrung
 *  {"blocked": {"de": "...", "en": "..."}}              Gesperrter Pfad → Klärung
 */
final class Evaluator
{
    /**
     * @param  array<string, mixed>  $node
     * @param  array<string, string>  $answers  Fragecode → yes|no|unknown
     */
    public function evaluate(array $node, array $answers, RecipientGroup $group): EvaluatedNode
    {
        if (array_key_exists('all', $node) || array_key_exists('any', $node)) {
            $kind = array_key_exists('all', $node) ? 'all' : 'any';
            $children = array_map(fn (array $c) => $this->evaluate($c, $answers, $group), $node[$kind]);
            $values = array_map(fn (EvaluatedNode $c) => $c->value, $children);

            return new EvaluatedNode(
                kind: $kind === 'all' ? 'and' : 'or',
                value: $kind === 'all' ? Truth::all($values) : Truth::any($values),
                children: $children,
                data: ['label' => $node['label'] ?? null],
            );
        }

        if (array_key_exists('not', $node)) {
            $child = $this->evaluate($node['not'], $answers, $group);

            return new EvaluatedNode('not', $child->value->negate(), [$child]);
        }

        if (array_key_exists('question', $node)) {
            $code = $node['question'];
            $answer = $answers[$code] ?? null;

            return new EvaluatedNode('question', Truth::fromAnswer($answer), data: [
                'question' => $code,
                'answer' => $answer,
                'answered' => $answer !== null,
            ]);
        }

        if (array_key_exists('recipient', $node)) {
            $value = Truth::tryFrom($node['recipient'][$group->value] ?? 'B') ?? Truth::Blocked;

            return new EvaluatedNode('recipient', $value, data: [
                'group' => $group->value,
                'note' => $node['note'] ?? null,
            ]);
        }

        if (array_key_exists('prior', $node)) {
            $code = $node['prior'];
            $mode = $node['mode'] ?? 'unclear';
            $answer = $answers[$code] ?? null;

            $value = match ($answer) {
                Answer::Yes->value => $mode === 'exclude' ? Truth::False : Truth::Blocked,
                Answer::No->value => Truth::True,
                default => Truth::Unknown,
            };

            return new EvaluatedNode('prior', $value, data: [
                'question' => $code,
                'mode' => $mode,
                'answer' => $answer,
                'answered' => $answer !== null,
            ]);
        }

        if (array_key_exists('blocked', $node)) {
            return new EvaluatedNode('blocked', Truth::Blocked, data: ['reason' => $node['blocked']]);
        }

        throw new InvalidArgumentException('Unbekannter Knotentyp: '.json_encode($node));
    }

    /**
     * Alle Fragecodes, die ein Ausdruck verwendet.
     *
     * @param  array<string, mixed>  $node
     * @return list<string>
     */
    public static function questionCodes(array $node): array
    {
        $codes = [];

        foreach (['all', 'any'] as $key) {
            foreach ($node[$key] ?? [] as $child) {
                $codes = [...$codes, ...self::questionCodes($child)];
            }
        }

        if (isset($node['not'])) {
            $codes = [...$codes, ...self::questionCodes($node['not'])];
        }

        foreach (['question', 'prior'] as $key) {
            if (isset($node[$key])) {
                $codes[] = $node[$key];
            }
        }

        return array_values(array_unique($codes));
    }
}
