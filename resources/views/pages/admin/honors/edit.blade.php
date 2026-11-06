<?php

use App\Models\Honor;
use App\Models\Procedure;
use App\Services\Ehrungsfinder\RuleWorkflow;
use App\Support\Localized;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public Honor $honor;

    /** @var array<string, array<string, string>> */
    public array $texts = [];

    /** @var array<string, array<string, string>> */
    public array $procedureTexts = [];

    public ?string $applicationUrl = null;

    public ?string $feeAmount = null;

    public ?string $feeCurrency = 'EUR';

    public bool $active = true;

    public int $sort = 0;

    public function mount(Honor $honor): void
    {
        $this->honor = $honor->load(['organisation', 'procedure']);

        foreach (['title', 'scope'] as $field) {
            foreach (Localized::locales() as $locale) {
                $this->texts[$field][$locale] = $honor->{$field}[$locale] ?? '';
            }
        }

        $procedure = $honor->procedure ?? new Procedure;
        foreach (Procedure::TEXT_FIELDS as $field) {
            foreach (Localized::locales() as $locale) {
                $this->procedureTexts[$field][$locale] = $procedure->{$field}[$locale] ?? '';
            }
        }

        $this->applicationUrl = $procedure->application_url;
        $this->feeAmount = $procedure->fee_amount;
        $this->feeCurrency = $procedure->fee_currency ?? 'EUR';
        $this->active = $honor->active;
        $this->sort = $honor->sort;
    }

    #[Computed]
    public function canEdit(): bool
    {
        return Auth::user()->canEditHonor($this->honor);
    }

    #[Computed]
    public function versions()
    {
        return $this->honor->ruleVersions()->with(['creator', 'approver'])->get();
    }

    public function save(): void
    {
        abort_unless($this->canEdit, 403);

        $this->validate([
            'texts.title.de' => ['required', 'string', 'max:200'],
            'texts.*.*' => ['nullable', 'string', 'max:2000'],
            'procedureTexts.*.*' => ['nullable', 'string', 'max:5000'],
            'applicationUrl' => ['nullable', 'url', 'max:1000'],
            'feeAmount' => ['nullable', 'numeric', 'min:0'],
            'feeCurrency' => ['nullable', 'string', 'size:3'],
            'sort' => ['integer'],
        ]);

        $clean = fn (array $values) => array_filter($values, fn ($v) => filled($v)) ?: null;

        $this->honor->update([
            'title' => $clean($this->texts['title']),
            'scope' => $clean($this->texts['scope']),
            'active' => $this->active,
            'sort' => $this->sort,
        ]);

        $this->honor->procedure()->updateOrCreate([], array_map($clean, $this->procedureTexts) + [
            'application_url' => $this->applicationUrl ?: null,
            'fee_amount' => $this->feeAmount !== '' ? $this->feeAmount : null,
            'fee_currency' => $this->feeAmount ? strtoupper((string) $this->feeCurrency) : null,
        ]);

        Flux::toast(variant: 'success', text: __('Steckbrief gespeichert.'));
    }

    public function newDraft(RuleWorkflow $workflow): void
    {
        abort_unless($this->canEdit, 403);

        $draft = $workflow->createDraft($this->honor, Auth::user());

        $this->redirectRoute('admin.rules.show', $draft, navigate: true);
    }
}; ?>

@use('App\Support\Labels')

<div class="flex flex-col gap-8">
    <div class="flex flex-col gap-1">
        <flux:link :href="route('admin.honors.index')" wire:navigate class="text-sm">← {{ __('Ehrungen & Regeln') }}</flux:link>
        <flux:heading size="xl" level="1">{{ $honor->tr('title') }}</flux:heading>
        <flux:text>{{ Labels::level($honor->level) }} · {{ $honor->organisation->tr('name') }}@if ($honor->region) · {{ Labels::region($honor->region) }}@endif · <code>{{ $honor->code }}</code></flux:text>
    </div>

    {{-- Regelversionen --}}
    <section class="flex flex-col gap-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:heading size="lg" level="2">{{ __('Regelversionen') }}</flux:heading>
            @if ($this->canEdit)
                <flux:button wire:click="newDraft" icon="document-plus" size="sm">{{ __('Neuen Entwurf anlegen') }}</flux:button>
            @endif
        </div>
        <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 text-xs text-zinc-500 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-2 text-start">{{ __('Version') }}</th>
                        <th class="px-4 py-2 text-start">{{ __('Status') }}</th>
                        <th class="px-4 py-2 text-start">{{ __('Regelgrundlage') }}</th>
                        <th class="px-4 py-2 text-start">{{ __('Gültig ab') }}</th>
                        <th class="px-4 py-2 text-start">{{ __('Freigabe') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->versions as $version)
                        <tr class="border-t border-zinc-200 dark:border-zinc-700">
                            <td class="px-4 py-2"><flux:link :href="route('admin.rules.show', $version)" wire:navigate>{{ $version->label() }}</flux:link></td>
                            <td class="px-4 py-2"><flux:badge size="sm" :color="Labels::versionStatusColor($version->status)">{{ Labels::versionStatus($version->status) }}</flux:badge></td>
                            <td class="px-4 py-2">{{ Labels::basis($version->basis) }}</td>
                            <td class="px-4 py-2">{{ $version->valid_from?->isoFormat('L') ?? '–' }}</td>
                            <td class="px-4 py-2">{{ $version->approved_at ? $version->approved_at->isoFormat('L').' · '.($version->approver?->name ?? $version->approval_role) : ($version->approval_role ?? '–') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-3 text-zinc-500">{{ __('Noch keine Regelversion.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Steckbrief --}}
    <form wire:submit="save" class="flex flex-col gap-6">
        <flux:heading size="lg" level="2">{{ __('Steckbrief') }}</flux:heading>
        @unless ($this->canEdit)
            <flux:callout icon="lock-closed" color="zinc"><flux:callout.text>{{ __('Du kannst diesen Steckbrief ansehen, aber nicht bearbeiten.') }}</flux:callout.text></flux:callout>
        @endunless

        <fieldset @disabled(! $this->canEdit) class="flex flex-col gap-6">
            <div class="grid gap-4 md:grid-cols-2">
                @foreach (Localized::locales() as $locale)
                    <flux:input wire:model="texts.title.{{ $locale }}" :label="__('Offizieller Name').' ('.strtoupper($locale).')'" />
                @endforeach
                @foreach (Localized::locales() as $locale)
                    <flux:textarea wire:model="texts.scope.{{ $locale }}" rows="2" :label="__('Zweck / Würdigung').' ('.strtoupper($locale).')'" />
                @endforeach
            </div>

            @php
                $labels = [
                    'next_step' => __('Nächster Antragsschritt'),
                    'nomination_right' => __('Vorschlagsrecht'),
                    'decision_body' => __('Entscheidungsgremium'),
                    'application' => __('Antragsweg'),
                    'deadlines' => __('Fristen'),
                    'lead_time' => __('Vorlauf'),
                    'evidence' => __('Nachweise'),
                ];
            @endphp
            @foreach (Procedure::TEXT_FIELDS as $field)
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach (Localized::locales() as $locale)
                        <flux:textarea wire:model="procedureTexts.{{ $field }}.{{ $locale }}" rows="2" :label="$labels[$field].' ('.strtoupper($locale).')'" />
                    @endforeach
                </div>
            @endforeach

            <div class="grid gap-4 md:grid-cols-4">
                <div class="md:col-span-2"><flux:input wire:model="applicationUrl" type="url" :label="__('Link zum offiziellen Antrag')" /></div>
                <flux:input wire:model="feeAmount" type="number" step="0.01" :label="__('Gebühr')" />
                <flux:input wire:model="feeCurrency" maxlength="3" :label="__('Währung')" />
            </div>

            <div class="flex flex-wrap items-center gap-6">
                <flux:switch wire:model="active" :label="__('Im Frageassistenten anzeigen')" />
                <flux:input wire:model="sort" type="number" class="max-w-32" :label="__('Reihenfolge')" />
            </div>
        </fieldset>

        @if ($this->canEdit)
            <div><flux:button type="submit" variant="primary">{{ __('Speichern') }}</flux:button></div>
        @endif
    </form>
</div>
