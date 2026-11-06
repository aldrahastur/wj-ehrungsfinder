@use('App\Support\Labels')
@use('App\Support\Localized')

@props(['node'])

@php
    $group = isset($node['all']) ? 'all' : (isset($node['any']) ? 'any' : null);
@endphp

<li class="py-0.5">
    @if ($group)
        <span class="text-xs font-semibold text-zinc-500">
            {{ isset($node['label']) ? Localized::pick($node['label']) : ($group === 'all' ? __('Alle Bedingungen erforderlich') : __('Einer der Zugänge genügt')) }}
            <span class="font-mono">({{ $group === 'all' ? 'UND' : 'ODER' }})</span>
        </span>
        <ul class="ms-2 border-s-2 border-zinc-200 ps-3 dark:border-zinc-700">
            @foreach ($node[$group] as $child)
                <x-admin.criteria-structure :node="$child" />
            @endforeach
        </ul>
    @elseif (isset($node['not']))
        <span class="text-xs font-semibold text-zinc-500">{{ __('Darf nicht zutreffen') }} <span class="font-mono">(NICHT)</span></span>
        <ul class="ms-2 border-s-2 border-zinc-200 ps-3 dark:border-zinc-700"><x-admin.criteria-structure :node="$node['not']" /></ul>
    @elseif (isset($node['question']))
        {{ Labels::questionCriterion($node['question']) }} <code class="text-xs text-zinc-500">{{ $node['question'] }}</code>
    @elseif (isset($node['prior']))
        {{ ($node['mode'] ?? 'unclear') === 'exclude' ? __('Erneute Verleihung ausgeschlossen') : __('Regel zur erneuten Verleihung nicht belegt') }}
        <code class="text-xs text-zinc-500">{{ $node['prior'] }}</code>
    @elseif (isset($node['recipient']))
        {{ __('Personengruppen') }}:
        @foreach ($node['recipient'] as $grp => $value)
            <span class="whitespace-nowrap">{{ Labels::group($grp) }} {{ ['T' => '✓', 'F' => '✕', 'B' => '!'][$value] ?? $value }}</span>@unless ($loop->last), @endunless
        @endforeach
    @elseif (isset($node['blocked']))
        <span class="text-violet-700 dark:text-violet-300">! {{ Localized::pick($node['blocked']) }}</span>
    @endif
</li>
