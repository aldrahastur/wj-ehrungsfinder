@use('App\Support\Labels')
@use('App\Support\Localized')

@props(['node'])

@php
    $answered = $node->wasAnswered();
    $mark = ($node->value->value === 'U' && ! $answered) ? 'N' : $node->value->value;
    $symbols = ['T' => '✓', 'F' => '✕', 'U' => '?', 'B' => '!', 'N' => '–'];
    $classes = [
        'T' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
        'F' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
        'U' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
        'B' => 'bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-300',
        'N' => 'border border-zinc-300 text-zinc-500 dark:border-zinc-600',
    ];

    $label = match ($node->kind) {
        'and' => $node->data['label'] ? Localized::pick($node->data['label']) : __('Alle Bedingungen erforderlich'),
        'or' => $node->data['label'] ? Localized::pick($node->data['label']) : __('Einer der Zugänge genügt'),
        'not' => __('Darf nicht zutreffen'),
        'question' => Labels::questionCriterion($node->data['question']),
        'recipient' => __('Personengruppe kann geehrt werden').' ('.Labels::group($node->data['group']).')'
            .($node->value->value === 'F' && $node->data['note'] ? ', '.Localized::pick($node->data['note']) : ''),
        'prior' => match ($node->value->value) {
            'F' => __('Erneute Verleihung ausgeschlossen'),
            'B' => __('Regel zur erneuten Verleihung nicht belegt'),
            default => Labels::questionCriterion($node->data['question']),
        },
        'blocked' => Localized::pick($node->data['reason']),
        default => $node->kind,
    };
@endphp

<li class="py-0.5">
    <div class="flex items-start gap-2">
        <span class="mt-0.5 inline-flex size-[18px] shrink-0 items-center justify-center rounded-full text-[11px] font-bold {{ $classes[$mark] }}" aria-hidden="true">{{ $symbols[$mark] }}</span>
        <span class="sr-only">{{ Labels::truth($node->value->value, $answered) }}:</span>
        <span class="{{ $node->isGroup() ? 'text-xs font-semibold text-zinc-500 dark:text-zinc-400' : '' }}">
            {{ $label }}
            @unless ($node->isGroup())
                <span class="text-xs text-zinc-500 dark:text-zinc-400">· {{ Labels::truth($node->value->value, $answered) }}</span>
            @endunless
        </span>
    </div>

    @if ($node->isGroup())
        <ul class="ms-2 border-s-2 border-zinc-200 ps-3 dark:border-zinc-700">
            @foreach ($node->children as $child)
                <x-finder.criteria-node :node="$child" />
            @endforeach
        </ul>
    @endif
</li>
