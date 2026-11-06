@use('App\Domain\Ehrungen\ResultState')
@use('App\Support\Labels')

@props(['result'])

@php
    $honor = $result->honor;
    $assessment = $result->assessment;
    $version = $result->ruleVersion;
    $state = $assessment->state;
    $stripe = [
        'suitable' => 'border-t-green-600',
        'possible' => 'border-t-amber-500',
        'not_suitable' => 'border-t-red-600',
        'clarification' => 'border-t-violet-600',
        'info' => 'border-t-accent',
    ][$state->value];
@endphp

<article {{ $attributes->class("flex flex-col overflow-hidden rounded-xl border border-t-4 border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 {$stripe}") }}>
    <div class="flex flex-col gap-2 p-5 pb-3">
        <flux:badge size="sm" :color="Labels::stateColor($state)" class="self-start">{{ Labels::state($state) }}</flux:badge>
        <h3 class="text-lg font-semibold leading-snug text-balance">{{ $honor->tr('title') }}</h3>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ Labels::level($honor->level) }} · {{ $honor->organisation->tr('name') }}</p>
    </div>

    <div class="flex flex-col gap-3 px-5 pb-5 text-sm">
        <p>{{ Labels::stateExplanation($state) }}</p>

        @if ($reason = Labels::reason($assessment->reason, $assessment->reasonDetail))
            <div class="rounded-lg bg-violet-50 p-3 dark:bg-violet-950/40">
                <p class="text-xs font-semibold uppercase tracking-wide text-violet-800 dark:text-violet-300">{{ __('Grund für die Klärung') }}</p>
                <p class="mt-1">{{ $reason }}</p>
                @if ($state === ResultState::Clarification && $assessment->raw?->value === 'T')
                    <p class="mt-1 text-zinc-600 dark:text-zinc-400">{{ __('Nach deinen Angaben wären die recherchierten Kriterien erfüllt.') }}</p>
                @endif
            </div>
        @endif

        @if ($assessment->tree)
            <div>
                <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Kriterien') }}</p>
                <ul>
                    <x-finder.criteria-node :node="$assessment->tree" />
                </ul>
            </div>
        @elseif ($version?->basis_note)
            <p class="text-zinc-600 dark:text-zinc-400">{{ $version->tr('basis_note') }}</p>
        @endif

        @if ($next = $honor->procedure?->tr('next_step'))
            <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900/60">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Nächster Schritt') }}</p>
                <p class="mt-1">{{ $next }}</p>
                @if ($honor->procedure->application_url)
                    <flux:link :href="$honor->procedure->application_url" external class="mt-1 inline-block">{{ __('Zum offiziellen Antrag') }}</flux:link>
                @endif
            </div>
        @endif

        <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900/60">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Quellenstand') }}</p>
            @if ($version)
                <p class="mt-1">
                    {{ Labels::basis($version->basis) }} · {{ __('Regelversion') }} {{ $version->label() }}
                    @if ($version->valid_from) · {{ __('gültig ab') }} {{ $version->valid_from->isoFormat('LL') }} @endif
                </p>
                <ul class="mt-1 list-disc ps-4">
                    @foreach ($version->sources as $source)
                        <li>
                            <a href="{{ $source->url }}" target="_blank" rel="noopener" class="underline underline-offset-2">{{ $source->code }}: {{ $source->title }}</a>
                            @if ($source->retrieved_at)<span class="text-zinc-500"> ({{ __('abgerufen') }} {{ $source->retrieved_at->isoFormat('L') }})</span>@endif
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="mt-1">{{ __('Keine freigegebene Regelversion.') }}</p>
            @endif
        </div>
    </div>
</article>
