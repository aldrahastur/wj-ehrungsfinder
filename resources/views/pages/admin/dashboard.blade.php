<?php

use App\Models\Assessment;
use App\Models\Feedback;
use App\Models\Honor;
use App\Models\RuleVersion;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Übersicht')] class extends Component {
    /**
     * Anonyme Kennzahlen der letzten 30 Tage.
     *
     * @return array<string, int|float|null>
     */
    #[Computed]
    public function stats(): array
    {
        $recent = Assessment::query()->where('created_at', '>=', now()->subDays(30))->get(['completed', 'duration_seconds', 'results']);
        $results = $recent->flatMap(fn ($a) => $a->results);
        $evaluated = $results->where('state', '!=', 'info');

        return [
            'assessments' => $recent->count(),
            'completion' => $recent->count() ? round(100 * $recent->where('completed', true)->count() / $recent->count()) : null,
            'median_minutes' => ($d = $recent->pluck('duration_seconds')->filter()->median()) ? round($d / 60, 1) : null,
            'clarification_share' => $evaluated->count() ? round(100 * $evaluated->where('state', 'clarification')->count() / $evaluated->count()) : null,
            'open_feedback' => Feedback::query()->where('status', 'open')->count(),
        ];
    }

    #[Computed]
    public function inReview()
    {
        return RuleVersion::query()->with('honor')->where('status', RuleVersion::STATUS_IN_REVIEW)->latest('submitted_at')->get();
    }

    #[Computed]
    public function honors()
    {
        return Honor::query()->ordered()->with(['organisation', 'publishedRule'])->withCount(['ruleVersions as drafts_count' => fn ($q) => $q->where('status', RuleVersion::STATUS_DRAFT)])->get();
    }
}; ?>

@use('App\Support\Labels')

<div class="flex flex-col gap-6">
    <flux:heading size="xl" level="1">{{ __('Übersicht') }}</flux:heading>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        @foreach ([
            ['label' => __('Vorprüfungen (30 Tage)'), 'value' => $this->stats['assessments']],
            ['label' => __('Abschlussquote'), 'value' => $this->stats['completion'] !== null ? $this->stats['completion'].' %' : '–'],
            ['label' => __('Bearbeitungszeit (Median)'), 'value' => $this->stats['median_minutes'] !== null ? $this->stats['median_minutes'].' min' : '–'],
            ['label' => __('Anteil „Klärung erforderlich“'), 'value' => $this->stats['clarification_share'] !== null ? $this->stats['clarification_share'].' %' : '–'],
            ['label' => __('Offene Rückmeldungen'), 'value' => $this->stats['open_feedback']],
        ] as $tile)
            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:text class="text-xs">{{ $tile['label'] }}</flux:text>
                <p class="mt-1 text-2xl font-semibold tabular-nums">{{ $tile['value'] }}</p>
            </div>
        @endforeach
    </div>

    @if ($this->inReview->isNotEmpty())
        <section class="flex flex-col gap-2">
            <flux:heading size="lg" level="2">{{ __('Warten auf Freigabe') }}</flux:heading>
            @foreach ($this->inReview as $version)
                <a href="{{ route('admin.rules.show', $version) }}" wire:navigate class="flex items-center justify-between rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 dark:border-amber-700 dark:bg-amber-950/30">
                    <span>{{ $version->honor->tr('title') }} · {{ $version->label() }}</span>
                    <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $version->submitted_at?->diffForHumans() }}</span>
                </a>
            @endforeach
        </section>
    @endif

    <section class="flex flex-col gap-2">
        <flux:heading size="lg" level="2">{{ __('Regelstand je Ehrung') }}</flux:heading>
        <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 text-xs text-zinc-500 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-2 text-start">{{ __('Ehrung') }}</th>
                        <th class="px-4 py-2 text-start">{{ __('Regelpaket') }}</th>
                        <th class="px-4 py-2 text-start">{{ __('Veröffentlicht') }}</th>
                        <th class="px-4 py-2 text-start">{{ __('Regelgrundlage') }}</th>
                        <th class="px-4 py-2 text-start">{{ __('Entwürfe') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->honors as $honor)
                        <tr class="border-t border-zinc-200 dark:border-zinc-700">
                            <td class="px-4 py-2"><flux:link :href="route('admin.honors.edit', $honor)" wire:navigate>{{ $honor->tr('title') }}</flux:link></td>
                            <td class="px-4 py-2">{{ $honor->organisation->tr('name') }}</td>
                            <td class="px-4 py-2">{{ $honor->publishedRule?->label() ?? '–' }}</td>
                            <td class="px-4 py-2">@if ($honor->publishedRule)<flux:badge size="sm">{{ Labels::basis($honor->publishedRule->basis) }}</flux:badge>@else<flux:badge size="sm" color="violet">{{ __('keine') }}</flux:badge>@endif</td>
                            <td class="px-4 py-2 tabular-nums">{{ $honor->drafts_count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <flux:text class="text-sm">{{ __('Kennzahlen sind anonym. Ehrungsentscheidungen selbst werden nicht als Erfolgswert erfasst.') }} {{ __('Pilotbetrieb') }}: {{ config('ehrungsfinder.pilot_mode') ? __('an') : __('aus') }}.</flux:text>
</div>
