@use('App\Support\Labels')
@use('App\Support\Localized')
@php
    $answered = $node->wasAnswered();
    $mark = ['T' => '✓', 'F' => '✕', 'U' => '?', 'B' => '!'][$node->value->value];
    if ($node->value->value === 'U' && ! $answered) { $mark = '–'; }
    $label = match ($node->kind) {
        'and' => $node->data['label'] ? Localized::pick($node->data['label']) : __('Alle Bedingungen erforderlich'),
        'or' => $node->data['label'] ? Localized::pick($node->data['label']) : __('Einer der Zugänge genügt'),
        'not' => __('Darf nicht zutreffen'),
        'question' => Labels::questionCriterion($node->data['question']),
        'recipient' => __('Personengruppe kann geehrt werden').' ('.Labels::group($node->data['group']).')',
        'prior' => $node->value->value === 'F' ? __('Erneute Verleihung ausgeschlossen') : ($node->value->value === 'B' ? __('Regel zur erneuten Verleihung nicht belegt') : Labels::questionCriterion($node->data['question'])),
        'blocked' => Localized::pick($node->data['reason']),
        default => $node->kind,
    };
@endphp
<li>
    <strong>{{ $mark }}</strong> {{ $label }}@unless ($node->isGroup()) <span class="muted">· {{ Labels::truth($node->value->value, $answered) }}</span>@endunless
    @if ($node->isGroup())
        <ul>
            @foreach ($node->children as $child)
                @include('finder.partials.print-node', ['node' => $child])
            @endforeach
        </ul>
    @endif
</li>
