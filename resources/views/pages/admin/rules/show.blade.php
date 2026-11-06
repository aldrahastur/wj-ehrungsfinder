<?php

use App\Domain\Ehrungen\RuleBasis;
use App\Models\RuleVersion;
use App\Models\Source;
use App\Services\Ehrungsfinder\ReferenceCaseRunner;
use App\Services\Ehrungsfinder\RuleWorkflow;
use App\Support\Localized;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public RuleVersion $ruleVersion;

    public string $basis = '';

    /** @var array<string, string> */
    public array $basisNote = [];

    public ?string $validFrom = null;

    public ?string $validUntil = null;

    public ?string $changeNote = null;

    public string $criteriaJson = '';

    /** @var list<int> */
    public array $sourceIds = [];

    public string $workflowNote = '';

    public string $approvalRole = '';

    public function mount(RuleVersion $ruleVersion): void
    {
        $this->ruleVersion = $ruleVersion->load(['honor.organisation', 'sources', 'events.user', 'submitter', 'approver']);
        $this->fillForm();
    }

    private function fillForm(): void
    {
        $v = $this->ruleVersion;
        $this->basis = $v->basis->value;
        foreach (Localized::locales() as $locale) {
            $this->basisNote[$locale] = $v->basis_note[$locale] ?? '';
        }
        $this->validFrom = $v->valid_from?->toDateString();
        $this->validUntil = $v->valid_until?->toDateString();
        $this->changeNote = $v->change_note;
        $this->criteriaJson = $v->criteria ? json_encode($v->criteria, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        $this->sourceIds = $v->sources->pluck('id')->all();
        $this->approvalRole = 'Fachliche Freigabe '.$v->honor->organisation->tr('name');
    }

    #[Computed]
    public function canEdit(): bool
    {
        return $this->ruleVersion->isEditable() && Auth::user()->canEditHonor($this->ruleVersion->honor);
    }

    #[Computed]
    public function canApprove(): bool
    {
        return $this->ruleVersion->status === RuleVersion::STATUS_IN_REVIEW && Auth::user()->canApproveHonor($this->ruleVersion->honor);
    }

    #[Computed]
    public function isOwnSubmission(): bool
    {
        return config('ehrungsfinder.four_eyes') && $this->ruleVersion->submitted_by === Auth::id();
    }

    #[Computed]
    public function sources()
    {
        return Source::query()->orderBy('code')->get();
    }

    #[Computed]
    public function published(): ?RuleVersion
    {
        return $this->ruleVersion->honor->publishedRule()->whereKeyNot($this->ruleVersion->id)->first();
    }

    #[Computed]
    public function referenceResults()
    {
        return app(ReferenceCaseRunner::class)->run([$this->ruleVersion->honor_id => $this->ruleVersion]);
    }

    public function save(RuleWorkflow $workflow): void
    {
        abort_unless($this->canEdit, 403);

        $this->validate([
            'basis' => ['required', Rule::in(RuleBasis::values())],
            'basisNote.de' => ['nullable', 'string', 'max:2000'],
            'basisNote.*' => ['nullable', 'string', 'max:2000'],
            'validFrom' => ['nullable', 'date'],
            'validUntil' => ['nullable', 'date', 'after_or_equal:validFrom'],
            'changeNote' => ['nullable', 'string', 'max:2000'],
            'sourceIds' => ['array'],
            'sourceIds.*' => ['integer', 'exists:sources,id'],
        ]);

        $criteria = null;
        if (trim($this->criteriaJson) !== '') {
            $criteria = json_decode($this->criteriaJson, true);
            if (! is_array($criteria)) {
                throw ValidationException::withMessages(['criteriaJson' => __('Kein gültiges JSON: :error', ['error' => json_last_error_msg()])]);
            }
        }

        $errors = $workflow->validationErrors($criteria, RuleBasis::from($this->basis));
        if ($errors !== []) {
            throw ValidationException::withMessages(['criteriaJson' => $errors]);
        }

        $this->ruleVersion->update([
            'basis' => $this->basis,
            'basis_note' => array_filter($this->basisNote, 'filled') ?: null,
            'valid_from' => $this->validFrom ?: null,
            'valid_until' => $this->validUntil ?: null,
            'change_note' => $this->changeNote,
            'criteria' => $criteria,
        ]);
        $this->ruleVersion->sources()->sync($this->sourceIds);
        $workflow->log($this->ruleVersion, Auth::user(), 'updated');

        $this->refreshVersion();
        Flux::toast(variant: 'success', text: __('Entwurf gespeichert.'));
    }

    public function submit(RuleWorkflow $workflow): void
    {
        abort_unless($this->canEdit, 403);

        $workflow->submit($this->ruleVersion, Auth::user(), $this->workflowNote ?: null);
        $this->workflowNote = '';
        $this->refreshVersion();
        Flux::toast(variant: 'success', text: __('Zur Freigabe gegeben.'));
    }

    public function approve(RuleWorkflow $workflow): void
    {
        abort_unless($this->canApprove, 403);

        $this->validate(['approvalRole' => ['required', 'string', 'max:200']]);
        $workflow->approve($this->ruleVersion, Auth::user(), $this->approvalRole, $this->workflowNote ?: null);
        $this->workflowNote = '';
        $this->refreshVersion();
        Flux::toast(variant: 'success', text: __('Freigegeben und veröffentlicht.'));
    }

    public function reject(RuleWorkflow $workflow): void
    {
        abort_unless($this->canApprove, 403);

        $this->validate(['workflowNote' => ['required', 'string', 'min:5', 'max:2000']], ['workflowNote.required' => __('Bitte begründe die Zurückweisung.')]);
        $workflow->reject($this->ruleVersion, Auth::user(), $this->workflowNote);
        $this->workflowNote = '';
        $this->refreshVersion();
        Flux::toast(text: __('Zurück an die Redaktion.'));
    }

    public function deleteDraft(): void
    {
        abort_unless($this->canEdit, 403);

        $honor = $this->ruleVersion->honor;
        $this->ruleVersion->delete();
        $this->redirectRoute('admin.honors.edit', $honor, navigate: true);
    }

    private function refreshVersion(): void
    {
        $this->ruleVersion = $this->ruleVersion->fresh(['honor.organisation', 'sources', 'events.user', 'submitter', 'approver']);
        unset($this->canEdit, $this->canApprove, $this->isOwnSubmission, $this->published, $this->referenceResults);
        $this->fillForm();
    }
}; ?>

@use('App\Support\Labels')

<div class="flex flex-col gap-8">
    <div class="flex flex-col gap-2">
        <flux:link :href="route('admin.honors.edit', $ruleVersion->honor)" wire:navigate class="text-sm">← {{ $ruleVersion->honor->tr('title') }}</flux:link>
        <div class="flex flex-wrap items-center gap-3">
            <flux:heading size="xl" level="1">{{ __('Regelversion') }} {{ $ruleVersion->label() }}</flux:heading>
            <flux:badge :color="Labels::versionStatusColor($ruleVersion->status)">{{ Labels::versionStatus($ruleVersion->status) }}</flux:badge>
            <flux:badge>{{ Labels::basis($ruleVersion->basis) }}</flux:badge>
        </div>
        @if ($ruleVersion->approved_at)
            <flux:text>{{ __('Freigegeben am :date von :name als „:role“.', ['date' => $ruleVersion->approved_at->isoFormat('LLL'), 'name' => $ruleVersion->approver?->name ?? '–', 'role' => $ruleVersion->approval_role]) }}</flux:text>
        @elseif ($ruleVersion->approval_role)
            <flux:text>{{ $ruleVersion->approval_role }}</flux:text>
        @endif
    </div>

    @error('status')<flux:callout color="red" icon="exclamation-triangle"><flux:callout.text>{{ $message }}</flux:callout.text></flux:callout>@enderror
    @error('approval')<flux:callout color="red" icon="exclamation-triangle"><flux:callout.text>{{ $message }}</flux:callout.text></flux:callout>@enderror
    @error('reference_cases')<flux:callout color="red" icon="exclamation-triangle"><flux:callout.text>{{ $message }}</flux:callout.text></flux:callout>@enderror
    @error('sources')<flux:callout color="red" icon="exclamation-triangle"><flux:callout.text>{{ $message }}</flux:callout.text></flux:callout>@enderror

    <div class="grid gap-8 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
        {{-- Linke Spalte: Bearbeiten bzw. Ansehen --}}
        <div class="flex flex-col gap-6">
            @if ($this->canEdit)
                <form wire:submit="save" class="flex flex-col gap-5">
                    <flux:select wire:model="basis" :label="__('Regelgrundlage')">
                        @foreach (RuleBasis::cases() as $case)
                            <flux:select.option :value="$case->value">{{ Labels::basis($case) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <div class="grid gap-4 md:grid-cols-2">
                        @foreach (Localized::locales() as $locale)
                            <flux:textarea wire:model="basisNote.{{ $locale }}" rows="2" :label="__('Hinweis zum Regelstand').' ('.strtoupper($locale).')'" />
                        @endforeach
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <flux:input type="date" wire:model="validFrom" :label="__('Gültig ab')" />
                        <flux:input type="date" wire:model="validUntil" :label="__('Gültig bis')" />
                    </div>
                    <flux:checkbox.group wire:model="sourceIds" :label="__('Regelquellen')">
                        @foreach ($this->sources as $source)
                            <flux:checkbox :value="$source->id" :label="$source->code.': '.$source->title" />
                        @endforeach
                    </flux:checkbox.group>
                    <flux:textarea wire:model="criteriaJson" rows="18" class="font-mono text-xs" :label="__('Kriterien (JSON)')"
                        :description="__('Knoten: all / any (mit optionalem label), not, question, recipient (member/former/external: T, F oder B), prior (mode: exclude oder unclear), blocked. Für „Nur Information“ leer lassen.')" />
                    <flux:textarea wire:model="changeNote" rows="2" :label="__('Änderungsnotiz')" />
                    <div class="flex flex-wrap gap-3">
                        <flux:button type="submit" variant="primary">{{ __('Entwurf speichern') }}</flux:button>
                        <flux:button type="button" variant="danger" wire:click="deleteDraft" wire:confirm="{{ __('Entwurf wirklich löschen?') }}">{{ __('Entwurf löschen') }}</flux:button>
                    </div>
                </form>

                <section class="flex flex-col gap-3 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:heading size="lg" level="2">{{ __('Zur Freigabe geben') }}</flux:heading>
                    <flux:text>{{ __('Speichere zuerst deine Änderungen. Danach prüft eine Person mit Freigaberolle die Version. Erst mit ihrer ausdrücklichen Freigabe wird sie veröffentlicht.') }}</flux:text>
                    <flux:textarea wire:model="workflowNote" rows="2" :label="__('Notiz für die Freigabe')" />
                    <div><flux:button wire:click="submit" icon="paper-airplane">{{ __('Zur Freigabe geben') }}</flux:button></div>
                </section>
            @else
                <section class="flex flex-col gap-3">
                    <dl class="grid grid-cols-[max-content_1fr] gap-x-4 gap-y-1 text-sm">
                        <dt class="text-zinc-500">{{ __('Gültig') }}</dt><dd>{{ $ruleVersion->valid_from?->isoFormat('L') ?? '–' }} – {{ $ruleVersion->valid_until?->isoFormat('L') ?? __('offen') }}</dd>
                        <dt class="text-zinc-500">{{ __('Hinweis') }}</dt><dd>{{ $ruleVersion->tr('basis_note') ?: '–' }}</dd>
                        <dt class="text-zinc-500">{{ __('Änderungsnotiz') }}</dt><dd>{{ $ruleVersion->change_note ?: '–' }}</dd>
                        <dt class="text-zinc-500">{{ __('Quellen') }}</dt>
                        <dd>@foreach ($ruleVersion->sources as $source)<a class="underline" href="{{ $source->url }}" target="_blank" rel="noopener">{{ $source->code }}</a>@unless ($loop->last), @endunless @endforeach</dd>
                    </dl>
                    @if ($ruleVersion->criteria)
                        <details>
                            <summary class="cursor-pointer text-sm text-accent">{{ __('Kriterien als JSON') }}</summary>
                            <pre class="mt-2 overflow-x-auto rounded-lg bg-zinc-50 p-3 text-xs dark:bg-zinc-900">{{ json_encode($ruleVersion->criteria, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        </details>
                    @endif
                </section>
            @endif

            @if ($this->canApprove)
                <section class="flex flex-col gap-3 rounded-xl border border-amber-300 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-950/30">
                    <flux:heading size="lg" level="2">{{ __('Fachliche Freigabe') }}</flux:heading>
                    @if ($ruleVersion->submitter)
                        <flux:text>{{ __('Zur Freigabe gegeben von :name am :date.', ['name' => $ruleVersion->submitter->name, 'date' => $ruleVersion->submitted_at?->isoFormat('LLL')]) }}</flux:text>
                    @endif
                    @if ($this->isOwnSubmission)
                        <flux:callout color="amber" icon="eye"><flux:callout.text>{{ __('Vier-Augen-Prinzip: Du hast diese Version selbst eingereicht. Eine andere Person muss sie freigeben. Zurückweisen ist möglich.') }}</flux:callout.text></flux:callout>
                    @endif
                    <flux:input wire:model="approvalRole" :label="__('Freigabe in der Rolle')" />
                    <flux:textarea wire:model="workflowNote" rows="2" :label="__('Notiz (bei Zurückweisung Pflicht)')" />
                    <div class="flex flex-wrap gap-3">
                        <flux:button wire:click="approve" variant="primary" icon="check" :disabled="$this->isOwnSubmission">{{ __('Freigeben und veröffentlichen') }}</flux:button>
                        <flux:button wire:click="reject" icon="arrow-uturn-left">{{ __('Zurückweisen') }}</flux:button>
                    </div>
                </section>
            @endif
        </div>

        {{-- Rechte Spalte: Vorschau, Tests, Verlauf --}}
        <div class="flex flex-col gap-6">
            <section class="flex flex-col gap-2">
                <flux:heading size="lg" level="2">{{ __('Kriterien') }}</flux:heading>
                @if ($ruleVersion->criteria)
                    <ul class="text-sm"><x-admin.criteria-structure :node="$ruleVersion->criteria" /></ul>
                @else
                    <flux:text>{{ __('Keine Kriterien – nur Informationskarte.') }}</flux:text>
                @endif
                @if ($this->published && $this->published->criteria != $ruleVersion->criteria)
                    <details class="text-sm">
                        <summary class="cursor-pointer text-accent">{{ __('Vergleich mit veröffentlichter Version :v', ['v' => $this->published->label()]) }}</summary>
                        <ul class="mt-2"><x-admin.criteria-structure :node="$this->published->criteria ?? []" /></ul>
                    </details>
                @endif
            </section>

            <section class="flex flex-col gap-2">
                <flux:heading size="lg" level="2">{{ __('Referenzfälle mit dieser Version') }}</flux:heading>
                @php($failed = $this->referenceResults->where('passed', false)->count())
                <flux:badge :color="$failed ? 'red' : 'green'" class="self-start">
                    {{ $failed ? __(':n von :total abweichend', ['n' => $failed, 'total' => $this->referenceResults->count()]) : __('Alle :total wie erwartet', ['total' => $this->referenceResults->count()]) }}
                </flux:badge>
                <ul class="flex flex-col gap-1 text-sm">
                    @foreach ($this->referenceResults as $row)
                        <li>
                            {{ $row['passed'] ? '✓' : '✕' }} {{ $row['case']->name }}
                            @foreach ($row['mismatches'] as $code => $m)
                                <div class="ms-5 text-xs text-red-700 dark:text-red-300">{{ $code }}: {{ __('erwartet') }} {{ $m['expected'] ? Labels::state($m['expected']) : '–' }}, {{ __('ergibt') }} {{ $m['actual'] ? Labels::state($m['actual']) : '–' }}</div>
                            @endforeach
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="flex flex-col gap-2">
                <flux:heading size="lg" level="2">{{ __('Verlauf') }}</flux:heading>
                <ol class="flex flex-col gap-2 text-sm">
                    @foreach ($ruleVersion->events as $event)
                        <li class="border-s-2 border-zinc-200 ps-3 dark:border-zinc-700">
                            <span class="font-medium">{{ Labels::event($event->action) }}</span>
                            <span class="text-zinc-500">· {{ $event->created_at?->isoFormat('LLL') }} · {{ $event->user?->name ?? __('System') }}</span>
                            @if ($event->note)<p class="text-zinc-600 dark:text-zinc-400">{{ $event->note }}</p>@endif
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>
    </div>
</div>
