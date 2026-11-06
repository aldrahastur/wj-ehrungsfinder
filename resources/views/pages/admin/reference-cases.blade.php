<?php

use App\Domain\Ehrungen\RecipientGroup;
use App\Models\ReferenceCase;
use App\Services\Ehrungsfinder\Finder;
use App\Services\Ehrungsfinder\ReferenceCaseRunner;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Referenzfälle')] class extends Component {
    public ?int $editingId = null;

    public string $name = '';

    public ?string $region = null;

    public string $group = 'member';

    public string $answersJson = '{}';

    public string $expectedJson = '{}';

    public bool $showAsExample = false;

    #[Computed]
    public function results()
    {
        return app(ReferenceCaseRunner::class)->run();
    }

    public function edit(?int $id = null): void
    {
        Gate::authorize('manage-catalog');

        $case = $id ? ReferenceCase::findOrFail($id) : new ReferenceCase(['recipient_group' => 'member', 'region' => config('ehrungsfinder.default_region'), 'answers' => [], 'expected' => []]);
        $this->editingId = $case->id;
        $this->name = (string) $case->name;
        $this->region = $case->region;
        $this->group = $case->recipient_group;
        $this->answersJson = json_encode((object) $case->answers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $this->expectedJson = json_encode((object) $case->expected, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $this->showAsExample = (bool) $case->show_as_example;
        $this->resetValidation();

        Flux::modal('case-form')->show();
    }

    /**
     * Erwartete Ergebnisse aus der aktuellen Auswertung übernehmen – bewusst als eigener Schritt,
     * damit fachlich geprüft wird, was als „richtig“ gilt.
     */
    public function useCurrentResult(): void
    {
        $answers = $this->decode('answersJson');
        $actual = app(Finder::class)
            ->assess($this->region, RecipientGroup::from($this->group), $answers, pilotMode: true)
            ->mapWithKeys(fn ($r) => [$r->honor->code => $r->assessment->state->value]);

        $this->expectedJson = json_encode($actual, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function save(): void
    {
        Gate::authorize('manage-catalog');

        $this->validate([
            'name' => ['required', 'string', 'max:200'],
            'region' => ['nullable', Rule::in(array_keys(config('ehrungsfinder.regions')))],
            'group' => ['required', Rule::in(RecipientGroup::values())],
        ]);

        $data = [
            'name' => $this->name,
            'region' => $this->region ?: null,
            'recipient_group' => $this->group,
            'answers' => $this->decode('answersJson'),
            'expected' => $this->decode('expectedJson'),
            'show_as_example' => $this->showAsExample,
        ];

        $this->editingId ? ReferenceCase::findOrFail($this->editingId)->update($data) : ReferenceCase::create($data);

        Flux::modal('case-form')->close();
        unset($this->results);
        Flux::toast(variant: 'success', text: __('Referenzfall gespeichert.'));
    }

    public function delete(int $id): void
    {
        Gate::authorize('manage-catalog');
        ReferenceCase::findOrFail($id)->delete();
        unset($this->results);
    }

    /**
     * @return array<string, string>
     */
    private function decode(string $property): array
    {
        $value = json_decode($this->{$property}, true);

        if (! is_array($value) || array_is_list($value) && $value !== []) {
            throw ValidationException::withMessages([$property => __('Bitte ein JSON-Objekt angeben, z. B. {"q_kreisprojekt": "yes"}.')]);
        }

        return $value;
    }
}; ?>

@use('App\Support\Labels')

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl" level="1">{{ __('Referenzfälle') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Anonymisierte Beispielsituationen mit fachlich erwartetem Ergebnis. Eine Regelversion wird nur freigegeben, wenn alle Fälle passen.') }}</flux:text>
        </div>
        @can('manage-catalog')
            <flux:button icon="plus" variant="primary" wire:click="edit">{{ __('Fall anlegen') }}</flux:button>
        @endcan
    </div>

    <div class="flex flex-col gap-3">
        @foreach ($this->results as $row)
            @php($case = $row['case'])
            <div class="rounded-xl border p-4 {{ $row['passed'] ? 'border-zinc-200 dark:border-zinc-700' : 'border-red-300 bg-red-50 dark:border-red-800 dark:bg-red-950/30' }}" wire:key="c-{{ $case->id }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-medium">{{ $row['passed'] ? '✓' : '✕' }} {{ $case->name }}</p>
                        <p class="text-sm text-zinc-500">{{ Labels::region($case->region) }} · {{ Labels::group($case->recipient_group) }} · {{ count($case->answers) }} {{ __('Antworten') }}@if ($case->show_as_example) · {{ __('als Beispiel im Assistenten') }}@endif</p>
                    </div>
                    @can('manage-catalog')
                        <div class="flex gap-1">
                            <flux:button size="xs" variant="ghost" wire:click="edit({{ $case->id }})">{{ __('Bearbeiten') }}</flux:button>
                            <flux:button size="xs" variant="ghost" wire:click="delete({{ $case->id }})" wire:confirm="{{ __('Referenzfall löschen?') }}">{{ __('Löschen') }}</flux:button>
                        </div>
                    @endcan
                </div>
                @foreach ($row['mismatches'] as $code => $m)
                    <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ $code }}: {{ __('erwartet') }} {{ $m['expected'] ? Labels::state($m['expected']) : '–' }}, {{ __('ergibt') }} {{ $m['actual'] ? Labels::state($m['actual']) : '–' }}</p>
                @endforeach
            </div>
        @endforeach
    </div>

    <flux:modal name="case-form" class="md:w-[44rem]">
        <form wire:submit="save" class="flex flex-col gap-4">
            <flux:heading size="lg">{{ $editingId ? __('Referenzfall bearbeiten') : __('Referenzfall anlegen') }}</flux:heading>
            <flux:input wire:model="name" :label="__('Beschreibung (anonym)')" />
            <div class="grid gap-3 md:grid-cols-2">
                <flux:select wire:model="region" :label="__('Landesverband')">
                    @foreach (config('ehrungsfinder.regions') as $code => $n)
                        <flux:select.option :value="$code">{{ $n }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="group" :label="__('Personengruppe')">
                    @foreach (['member', 'former', 'external'] as $g)
                        <flux:select.option :value="$g">{{ Labels::group($g) }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <flux:textarea wire:model="answersJson" rows="8" class="font-mono text-xs" :label="__('Antworten (JSON: Fragecode → yes | no | unknown)')" />
            <flux:textarea wire:model="expectedJson" rows="6" class="font-mono text-xs" :label="__('Erwartetes Ergebnis (JSON: Ehrungscode → suitable | possible | not_suitable | clarification | info)')" />
            <div><flux:button size="sm" wire:click="useCurrentResult" type="button" icon="arrow-down-on-square">{{ __('Aktuelles Ergebnis als Erwartung übernehmen') }}</flux:button></div>
            <flux:switch wire:model="showAsExample" :label="__('Als Beispiel im Assistenten anbieten')" />
            <div class="flex justify-end"><flux:button type="submit" variant="primary">{{ __('Speichern') }}</flux:button></div>
        </form>
    </flux:modal>
</div>
