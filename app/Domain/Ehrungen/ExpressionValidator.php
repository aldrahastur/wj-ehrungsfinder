<?php

namespace App\Domain\Ehrungen;

/**
 * Prüft einen Kriterienausdruck auf Struktur und bekannte Fragen,
 * bevor eine Regelversion gespeichert oder zur Freigabe gegeben wird.
 */
final class ExpressionValidator
{
    /**
     * @param  mixed  $node
     * @param  list<string>  $knownQuestions
     * @return list<string> Fehlermeldungen (leer = gültig)
     */
    public function validate(mixed $node, array $knownQuestions, string $path = '$'): array
    {
        if (! is_array($node) || array_is_list($node)) {
            return ["{$path}: Knoten muss ein Objekt sein."];
        }

        $types = array_values(array_intersect(array_keys($node), ['all', 'any', 'not', 'question', 'recipient', 'prior', 'blocked']));

        if (count($types) !== 1) {
            return ["{$path}: Genau einer der Schlüssel all, any, not, question, recipient, prior, blocked ist erforderlich."];
        }

        $type = $types[0];
        $errors = [];

        switch ($type) {
            case 'all':
            case 'any':
                if (! is_array($node[$type]) || ! array_is_list($node[$type]) || $node[$type] === []) {
                    return ["{$path}.{$type}: muss eine nicht-leere Liste sein."];
                }
                foreach ($node[$type] as $i => $child) {
                    $errors = [...$errors, ...$this->validate($child, $knownQuestions, "{$path}.{$type}[{$i}]")];
                }
                break;

            case 'not':
                $errors = $this->validate($node['not'], $knownQuestions, "{$path}.not");
                break;

            case 'question':
            case 'prior':
                if (! is_string($node[$type]) || ! in_array($node[$type], $knownQuestions, true)) {
                    $errors[] = "{$path}.{$type}: unbekannte Frage „".(is_string($node[$type]) ? $node[$type] : '?').'“.';
                }
                if ($type === 'prior' && ! in_array($node['mode'] ?? 'unclear', ['exclude', 'unclear'], true)) {
                    $errors[] = "{$path}.mode: erlaubt sind exclude oder unclear.";
                }
                break;

            case 'recipient':
                if (! is_array($node['recipient'])) {
                    $errors[] = "{$path}.recipient: muss ein Objekt sein.";
                    break;
                }
                foreach (RecipientGroup::values() as $group) {
                    if (! in_array($node['recipient'][$group] ?? null, ['T', 'F', 'B'], true)) {
                        $errors[] = "{$path}.recipient.{$group}: erlaubt sind T, F oder B.";
                    }
                }
                break;

            case 'blocked':
                if (! is_array($node['blocked']) || ! isset($node['blocked']['de'])) {
                    $errors[] = "{$path}.blocked: deutscher Begründungstext (de) fehlt.";
                }
                break;
        }

        return $errors;
    }
}
