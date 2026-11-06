<?php

namespace App\Domain\Ehrungen;

/**
 * Ausgewerteter Knoten eines Kriterienbaums, Grundlage für die Ergebniskarte.
 */
final readonly class EvaluatedNode
{
    /**
     * @param  'and'|'or'|'not'|'question'|'recipient'|'prior'|'blocked'  $kind
     * @param  list<EvaluatedNode>  $children
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public string $kind,
        public Truth $value,
        public array $children = [],
        public array $data = [],
    ) {}

    public function isGroup(): bool
    {
        return in_array($this->kind, ['and', 'or', 'not'], true);
    }

    /**
     * Wurde der Wert einer Frage tatsächlich angegeben (inkl. „Weiß ich nicht“)?
     */
    public function wasAnswered(): bool
    {
        return (bool) ($this->data['answered'] ?? true);
    }

    /**
     * @return list<EvaluatedNode>
     */
    public function leaves(): array
    {
        if (! $this->isGroup()) {
            return [$this];
        }

        return array_merge(...array_map(fn (self $c) => $c->leaves(), $this->children));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'value' => $this->value->value,
            'data' => $this->data,
            'children' => array_map(fn (self $c) => $c->toArray(), $this->children),
        ];
    }
}
