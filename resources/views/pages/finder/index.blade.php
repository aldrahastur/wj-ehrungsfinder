<?php

use App\Domain\Ehrungen\Answer;
use App\Domain\Ehrungen\RecipientGroup;
use App\Domain\Ehrungen\ResultState;
use App\Models\Assessment;
use App\Models\Feedback;
use App\Models\Question;
use App\Models\ReferenceCase;
use App\Services\Ehrungsfinder\Catalog;
use App\Services\Ehrungsfinder\Finder;
use App\Services\Ehrungsfinder\HonorResult;
use App\Support\Labels;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::public')] #[Title('Vorprüfung')] class extends Component {
    /** setup | question | result */
    public string $step = 'setup';

    public string $country = 'DE';

    public string $region = '';

    public string $group = 'member';

    /** @var array<string, string> Fragecode → yes|no|unknown */
    public array $answers = [];

    /** @var list<string> */
    public array $history = [];

    public ?string $current = null;

    public bool $editing = false;

    public ?string $assessmentUuid = null;

    public ?int $startedAt = null;

    public string $feedbackMessage = '';

    public bool $feedbackSent = false;

    public function mount(): void
    {
        $this->region = (string) config('ehrungsfinder.default_region');
    }

    public function start(): void
    {
        $this->validate([
            'country' => ['required', Rule::in(array_keys(config('ehrungsfinder.countries')))],
            'region' => ['required', Rule::in(array_keys(config('ehrungsfinder.regions')))],
            'group' => ['required', Rule::in(RecipientGroup::values())],
        ]);

        $this->resetRun();
        $this->advance();
    }

    public function loadExample(int $id): void
    {
        $case = ReferenceCase::query()->where('show_as_example', true)->findOrFail($id);

        $this->resetRun();
        $this->region = $case->region ?? $this->region;
        $this->group = $case->recipient_group;
        $this->answers = $case->answers;
        $this->advance();
    }

    public function answer(string $value): void
    {
        abort_unless($this->current && in_array($value, Answer::values(), true), 422);

        $this->answers[$this->current] = $value;

        if ($this->editing) {
            $this->editing = false;
            $this->showResults();

            return;
        }

        if (end($this->history) !== $this->current) {
            $this->history[] = $this->current;
        }

        $this->advance();
    }

    public function back(): void
    {
        if ($this->editing) {
            $this->editing = false;
            $this->showResults();

            return;
        }

        $previous = array_pop($this->history);

        if ($previous) {
            $this->current = $previous;
        } else {
            $this->step = 'setup';
            $this->current = null;
        }
    }

    public function editAnswer(string $code): void
    {
        abort_unless(app(Catalog::class)->question($code), 404);

        $this->current = $code;
        $this->editing = true;
        $this->step = 'question';
    }

    public function showResults(): void
    {
        $this->step = 'result';
        $this->current = null;
        $this->persist();
    }

    public function restart(): void
    {
        $this->resetRun();
        $this->step = 'setup';
    }

    public function sendFeedback(): void
    {
        $this->validate(['feedbackMessage' => ['required', 'string', 'min:5', 'max:2000']]);

        Feedback::create([
            'assessment_id' => Assessment::query()->where('uuid', $this->assessmentUuid)->value('id'),
            'message' => $this->feedbackMessage,
        ]);

        $this->feedbackMessage = '';
        $this->feedbackSent = true;
    }

    #[Computed]
    public function question(): ?Question
    {
        return $this->current ? app(Catalog::class)->question($this->current) : null;
    }

    #[Computed]
    public function remaining(): int
    {
        return $this->finder()->remainingQuestions($this->region, $this->groupEnum(), $this->answers);
    }

    /**
     * @return Collection<int, HonorResult>
     */
    #[Computed]
    public function results(): Collection
    {
        return $this->finder()->assess($this->region, $this->groupEnum(), $this->answers);
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function effectiveAnswers(): array
    {
        return $this->finder()->effectiveAnswers($this->region, $this->groupEnum(), $this->answers);
    }

    /**
     * @return Collection<int, string>
     */
    #[Computed]
    public function honorTitlesForQuestion(): Collection
    {
        if (! $this->current) {
            return collect();
        }

        $codes = $this->finder()->honorsUsing($this->current, $this->region, $this->groupEnum());

        return app(Catalog::class)->honors()->whereIn('code', $codes)->map(fn ($h) => $h->tr('title'))->values();
    }

    /**
     * @return Collection<int, ReferenceCase>
     */
    #[Computed]
    public function examples(): Collection
    {
        return ReferenceCase::query()->where('show_as_example', true)->orderBy('id')->get();
    }

    #[Computed]
    public function hasRegionalCatalog(): bool
    {
        return app(Catalog::class)->honors()->contains('region', $this->region);
    }

    private function advance(): void
    {
        $next = $this->finder()->nextQuestion($this->region, $this->groupEnum(), $this->answers);

        if ($next) {
            $this->current = $next->code;
            $this->step = 'question';
        } else {
            $this->showResults();
        }
    }

    private function persist(): void
    {
        unset($this->results, $this->remaining);

        $data = [
            'country' => $this->country,
            'region' => $this->region,
            'recipient_group' => $this->group,
            'locale' => app()->getLocale(),
            'answers' => $this->answers,
            'results' => $this->results->map(fn (HonorResult $r) => $r->toArray())->all(),
            'completed' => $this->remaining === 0,
            'duration_seconds' => $this->startedAt ? max(0, now()->timestamp - $this->startedAt) : null,
        ];

        $assessment = $this->assessmentUuid ? Assessment::query()->firstWhere('uuid', $this->assessmentUuid) : null;

        if ($assessment) {
            $assessment->update($data);
        } else {
            $this->assessmentUuid = Assessment::create($data + ['uuid' => (string) Str::uuid()])->uuid;
        }
    }

    private function resetRun(): void
    {
        $this->answers = [];
        $this->history = [];
        $this->current = null;
        $this->editing = false;
        $this->assessmentUuid = null;
        $this->startedAt = now()->timestamp;
        $this->feedbackSent = false;
        $this->feedbackMessage = '';
    }

    private function groupEnum(): RecipientGroup
    {
        return RecipientGroup::tryFrom($this->group) ?? RecipientGroup::Member;
    }

    private function finder(): Finder
    {
        return app(Finder::class);
    }
}; ?>


<div class="mx-auto flex flex-col gap-6">
    {{-- ───────────── Einstieg ───────────── --}}
    @if ($step === 'setup')
        <div class="mx-auto flex w-full max-w-xl flex-col gap-6">
            <section class="flex flex-col gap-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <div>
                    <flux:heading size="xl" level="1">{{ __('Welche persönlichen Ehrungen kommen infrage?') }}</flux:heading>
                    <flux:text class="mt-2">{{ __('Beantworte kurze Ja-/Nein-Fragen. Das dauert meist nur wenige Minuten. Name und Geburtsdatum werden nicht abgefragt.') }}</flux:text>
                </div>

                <form wire:submit="start" class="flex flex-col gap-5">
                    <flux:select wire:model="country" :label="__('Land und Nationalverband')">
                        @foreach (config('ehrungsfinder.countries') as $code => $country)
                            <flux:select.option :value="$code">{{ \App\Support\Localized::pick($country['name']) }}</flux:select.option>
                        @endforeach
                        <flux:select.option value="" disabled>{{ __('Weitere JCI-Nationalverbände (in Vorbereitung)') }}</flux:select.option>
                    </flux:select>

                    <flux:select wire:model="region" :label="__('Landesverband')" :description="__('Bestimmt, welche Landesehrungen geprüft werden.')">
                        @foreach (config('ehrungsfinder.regions') as $code => $name)
                            <flux:select.option :value="$code">{{ $name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:radio.group wire:model="group" :label="__('Die vorgeschlagene Person ist …')">
                        <flux:radio value="member" :label="__('Mitglied')" :description="__('aktuell Mitglied der Wirtschaftsjunioren')" />
                        <flux:radio value="former" :label="__('ehemaliges Mitglied')" :description="__('z. B. altersbedingt ausgeschieden')" />
                        <flux:radio value="external" :label="__('externe unterstützende Person')" :description="__('z. B. Förderin, Partner, Referent')" />
                    </flux:radio.group>

                    <div>
                        <flux:button type="submit" variant="primary" icon:trailing="arrow-right">{{ __('Prüfung starten') }}</flux:button>
                    </div>
                </form>
            </section>

            @if ($this->examples->isNotEmpty())
                <section class="flex flex-col gap-3">
                    <flux:text class="text-xs font-semibold uppercase tracking-wide">{{ __('Oder ein anonymisiertes Beispiel laden') }}</flux:text>
                    <div class="grid gap-3 sm:grid-cols-3">
                        @foreach ($this->examples as $example)
                            <button type="button" wire:click="loadExample({{ $example->id }})" class="rounded-lg border border-dashed border-zinc-300 p-3 text-start text-sm hover:border-accent focus-visible:outline-2 focus-visible:outline-accent dark:border-zinc-600">
                                <span class="block font-medium">{{ $example->name }}</span>
                                <span class="block text-zinc-500 dark:text-zinc-400">{{ Labels::region($example->region) }} · {{ Labels::group($example->recipient_group) }}</span>
                            </button>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    @endif

    {{-- ───────────── Frage ───────────── --}}
    @if ($step === 'question' && $this->question)
        @php
            $asked = count($history) + 1;
            $left = max(0, $this->remaining - (isset($this->effectiveAnswers[$current]) ? 0 : 1));
            $percent = (int) round(100 * $asked / max(1, $asked + $left));
            $currentAnswer = $answers[$current] ?? null;
        @endphp

        <section class="mx-auto flex w-full max-w-xl flex-col gap-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800" wire:key="q-{{ $current }}">
            <div class="flex flex-col gap-2">
                <flux:text class="text-xs font-semibold uppercase tracking-wide">
                    @if ($editing)
                        {{ __('Antwort ändern') }}
                    @else
                        {{ __('Frage :n · noch höchstens :left offen', ['n' => $asked, 'left' => $left]) }}
                    @endif
                </flux:text>
                <div class="h-1.5 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="h-full bg-brass motion-safe:transition-all" style="width: {{ $percent }}%"></div>
                </div>
            </div>

            <flux:heading size="xl" level="1" class="text-balance">{{ $this->question->tr('text') }}</flux:heading>

            <div class="grid grid-cols-2 gap-3">
                <flux:button wire:click="answer('yes')" :variant="$currentAnswer === 'yes' ? 'primary' : 'filled'" class="h-14 text-base" autofocus>{{ __('Ja') }}</flux:button>
                <flux:button wire:click="answer('no')" :variant="$currentAnswer === 'no' ? 'primary' : 'filled'" class="h-14 text-base">{{ __('Nein') }}</flux:button>
                <flux:button wire:click="answer('unknown')" :variant="$currentAnswer === 'unknown' ? 'primary' : 'ghost'" class="col-span-2">{{ __('Weiß ich nicht') }}</flux:button>
            </div>

            @if ($this->question->tr('explanation'))
                <details class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
                    <summary class="cursor-pointer font-medium text-accent">{{ __('Was ist gemeint?') }}</summary>
                    <flux:text class="mt-2">{{ $this->question->tr('explanation') }}</flux:text>
                </details>
            @endif

            @if ($this->honorTitlesForQuestion->isNotEmpty())
                <div class="flex flex-wrap items-center gap-2">
                    <flux:text class="text-xs">{{ __('Wird geprüft für') }}:</flux:text>
                    @foreach ($this->honorTitlesForQuestion as $title)
                        <flux:badge size="sm">{{ $title }}</flux:badge>
                    @endforeach
                </div>
            @endif

            <div class="flex items-center justify-between">
                <flux:button wire:click="back" variant="ghost" icon="arrow-left">{{ __('Zurück') }}</flux:button>
                @unless ($editing)
                    <flux:button wire:click="showResults" variant="ghost" size="sm">{{ __('Ergebnis jetzt anzeigen') }}</flux:button>
                @endunless
            </div>
        </section>
    @endif

    {{-- ───────────── Ergebnis ───────────── --}}
    @if ($step === 'result')
        @php
            $results = $this->results;
            $evaluable = $results->filter(fn ($r) => $r->assessment->state !== ResultState::Info);
            $noMatch = $evaluable->isNotEmpty() && $evaluable->every(fn ($r) => $r->assessment->state === ResultState::NotSuitable);
            $counts = $results->countBy(fn ($r) => $r->assessment->state->value);
        @endphp

        <section class="flex flex-col gap-3">
            <flux:heading size="xl" level="1">{{ __('Ergebnis der Vorprüfung') }}</flux:heading>
            <flux:text>{{ __('Landesverband') }} {{ Labels::region($region) }} · {{ Labels::group($group) }} · {{ now()->isoFormat('LL') }}</flux:text>
            <div class="flex flex-wrap gap-2">
                @foreach (['suitable', 'possible', 'clarification', 'not_suitable', 'info'] as $state)
                    @if ($counts[$state] ?? false)
                        <flux:badge :color="Labels::stateColor($state)">{{ $counts[$state] }} × {{ Labels::state($state) }}</flux:badge>
                    @endif
                @endforeach
            </div>
        </section>

        @unless ($this->hasRegionalCatalog)
            <flux:callout icon="information-circle" color="zinc">
                <flux:callout.text>{{ __('Persönliche Landesehrungen für :region sind noch nicht erfasst. Geprüft werden nur Ehrungen der WJD und von JCI.', ['region' => Labels::region($region)]) }}</flux:callout.text>
            </flux:callout>
        @endunless

        @if ($noMatch)
            <flux:callout icon="magnifying-glass" color="zinc" :heading="__('Keine passende Ehrung im hinterlegten Katalog gefunden')">
                <flux:callout.text>
                    {{ __('Bei Fragen hilft dir :contact weiter.', ['contact' => config('ehrungsfinder.contact.name')]) }}
                    @if (config('ehrungsfinder.contact.email'))
                        <flux:link :href="'mailto:'.config('ehrungsfinder.contact.email')">{{ config('ehrungsfinder.contact.email') }}</flux:link>
                    @endif
                </flux:callout.text>
            </flux:callout>
        @else
            <flux:text>{{ __('Mehrere Ehrungen können gleichzeitig passen. Sie stehen gleichrangig nebeneinander; eine höhere Ebene verdrängt keine andere.') }}</flux:text>
        @endif

        <div class="grid items-start gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($results as $result)
                <x-finder.result-card :result="$result" wire:key="r-{{ $result->honor->code }}" />
            @endforeach
        </div>

        <section class="flex flex-wrap items-center gap-3 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            @if ($assessmentUuid)
                <flux:button variant="primary" icon="printer" :href="route('result.show', $assessmentUuid)" target="_blank">{{ __('Druckansicht') }}</flux:button>
                <flux:button icon="arrow-down-tray" :href="route('result.download', $assessmentUuid)">{{ __('Herunterladen') }}</flux:button>
            @endif
            <flux:button wire:click="restart" variant="ghost" icon="arrow-path">{{ __('Neue Prüfung') }}</flux:button>
            <flux:text class="basis-full text-sm">{{ __('Die Übersicht verweist auf die offiziellen Anträge. Eingereicht wird ausschließlich über den offiziellen Antragsweg.') }}</flux:text>
        </section>

        <section class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg" level="2">{{ __('Deine Angaben') }}</flux:heading>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-start text-xs text-zinc-500 dark:border-zinc-700">
                            <th class="py-2 pe-3 text-start font-semibold">{{ __('Frage') }}</th>
                            <th class="py-2 pe-3 text-start font-semibold">{{ __('Antwort') }}</th>
                            <th class="py-2"><span class="sr-only">{{ __('Aktion') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (app(Catalog::class)->questions() as $q)
                            @continue(! isset($this->effectiveAnswers[$q->code]))
                            <tr class="border-b border-zinc-100 align-top dark:border-zinc-700/60" wire:key="a-{{ $q->code }}">
                                <td class="py-2 pe-3">{{ $q->tr('text') }}</td>
                                <td class="py-2 pe-3 whitespace-nowrap">
                                    {{ Labels::answer($this->effectiveAnswers[$q->code]) }}
                                    @unless (isset($answers[$q->code]))
                                        <span class="text-xs text-zinc-500">({{ __('abgeleitet') }})</span>
                                    @endunless
                                </td>
                                <td class="py-2 text-end">
                                    <flux:button size="xs" variant="ghost" wire:click="editAnswer('{{ $q->code }}')">{{ __('ändern') }}</flux:button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg" level="2">{{ __('Rückmeldung zum Tool') }}</flux:heading>
            @if ($feedbackSent)
                <flux:callout icon="check-circle" color="green">
                    <flux:callout.text>{{ __('Danke! Deine Rückmeldung ist beim Projektteam angekommen.') }}</flux:callout.text>
                </flux:callout>
            @else
                <flux:text>{{ __('War eine Frage unklar oder ein Ergebnis fachlich falsch? Beschreibe es kurz – bitte ohne Namen oder Angaben zu Kandidat*innen.') }}</flux:text>
                <form wire:submit="sendFeedback" class="flex flex-col gap-3">
                    <flux:textarea wire:model="feedbackMessage" :label="__('Rückmeldung')" rows="3" />
                    <div><flux:button type="submit">{{ __('Rückmeldung senden') }}</flux:button></div>
                </form>
            @endif
        </section>

        <flux:text class="text-sm">{{ __('Diese Vorprüfung ist eine Empfehlung auf Grundlage deiner Angaben und der hinterlegten Regeln. Über die Verleihung entscheiden die zuständigen Verbandsgremien. Qualitative Angaben sind Einschätzungen der vorschlagenden Person und werden mit Nachweisen geprüft.') }}</flux:text>
    @endif
</div>
