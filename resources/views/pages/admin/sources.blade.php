<?php

use App\Models\Source;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Quellen')] class extends Component {
    public ?int $editingId = null;

    public string $code = '';

    public string $title = '';

    public ?string $url = null;

    public ?string $noteDe = null;

    public ?string $retrievedAt = null;

    #[Computed]
    public function sources()
    {
        return Source::query()->withCount('ruleVersions')->orderBy('code')->get();
    }

    public function edit(?int $id = null): void
    {
        $s = $id ? Source::findOrFail($id) : new Source(['retrieved_at' => now()]);
        $this->editingId = $s->id;
        $this->code = (string) $s->code;
        $this->title = (string) $s->title;
        $this->url = $s->url;
        $this->noteDe = $s->note['de'] ?? null;
        $this->retrievedAt = $s->retrieved_at?->toDateString();
        $this->resetValidation();

        Flux::modal('source-form')->show();
    }

    public function save(): void
    {
        $this->validate([
            'code' => ['required', 'string', 'max:20', 'unique:sources,code,'.($this->editingId ?? 'NULL')],
            'title' => ['required', 'string', 'max:300'],
            'url' => ['nullable', 'url', 'max:1000'],
            'noteDe' => ['nullable', 'string', 'max:1000'],
            'retrievedAt' => ['nullable', 'date'],
        ]);

        $source = $this->editingId ? Source::findOrFail($this->editingId) : new Source;
        $source->fill([
            'code' => $this->code,
            'title' => $this->title,
            'url' => $this->url ?: null,
            'note' => $this->noteDe ? array_merge($source->note ?? [], ['de' => $this->noteDe]) : $source->note,
            'retrieved_at' => $this->retrievedAt ?: null,
        ])->save();

        Flux::modal('source-form')->close();
        unset($this->sources);
        Flux::toast(variant: 'success', text: __('Quelle gespeichert.'));
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl" level="1">{{ __('Quellen') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Ein Nachrichtenbeitrag belegt eine Verleihung, ersetzt aber keine Vergaberichtlinie. Veröffentlichungsdatum und aktuelle Gültigkeit bitte unterscheiden.') }}</flux:text>
        </div>
        <flux:button icon="plus" variant="primary" wire:click="edit">{{ __('Quelle anlegen') }}</flux:button>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-xs text-zinc-500 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-2 text-start">{{ __('Code') }}</th>
                    <th class="px-4 py-2 text-start">{{ __('Titel') }}</th>
                    <th class="px-4 py-2 text-start">{{ __('Abgerufen') }}</th>
                    <th class="px-4 py-2 text-start">{{ __('Regelversionen') }}</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($this->sources as $s)
                    <tr class="border-t border-zinc-200 align-top dark:border-zinc-700" wire:key="s-{{ $s->id }}">
                        <td class="px-4 py-2 font-medium">{{ $s->code }}</td>
                        <td class="px-4 py-2">
                            @if ($s->url)<a class="underline underline-offset-2" href="{{ $s->url }}" target="_blank" rel="noopener">{{ $s->title }}</a>@else{{ $s->title }}@endif
                            @if ($s->tr('note'))<p class="text-zinc-500">{{ $s->tr('note') }}</p>@endif
                        </td>
                        <td class="px-4 py-2">{{ $s->retrieved_at?->isoFormat('L') ?? '–' }}</td>
                        <td class="px-4 py-2 tabular-nums">{{ $s->rule_versions_count }}</td>
                        <td class="px-4 py-2 text-end"><flux:button size="xs" variant="ghost" wire:click="edit({{ $s->id }})">{{ __('Bearbeiten') }}</flux:button></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <flux:modal name="source-form" class="md:w-[36rem]">
        <form wire:submit="save" class="flex flex-col gap-4">
            <flux:heading size="lg">{{ $editingId ? __('Quelle bearbeiten') : __('Quelle anlegen') }}</flux:heading>
            <div class="grid gap-3 md:grid-cols-[8rem_1fr]">
                <flux:input wire:model="code" :label="__('Code')" placeholder="Q9" />
                <flux:input wire:model="title" :label="__('Titel')" />
            </div>
            <flux:input wire:model="url" type="url" :label="__('Link')" />
            <flux:textarea wire:model="noteDe" rows="2" :label="__('Hinweis (z. B. Abschnitt, Seiten)')" />
            <flux:input wire:model="retrievedAt" type="date" :label="__('Abgerufen am')" />
            <div class="flex justify-end"><flux:button type="submit" variant="primary">{{ __('Speichern') }}</flux:button></div>
        </form>
    </flux:modal>
</div>
