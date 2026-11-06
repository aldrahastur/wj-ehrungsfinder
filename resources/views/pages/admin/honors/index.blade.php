<?php

use App\Models\Honor;
use App\Models\Organisation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Ehrungen & Regeln')] class extends Component {
    public string $code = '';

    public string $titleDe = '';

    public ?int $organisationId = null;

    public string $level = 'national';

    public ?string $region = null;

    #[Computed]
    public function honors()
    {
        return Honor::query()->ordered()->with(['organisation', 'publishedRule'])->get();
    }

    #[Computed]
    public function organisations()
    {
        return Organisation::query()->orderBy('id')->get();
    }

    public function create(): void
    {
        Gate::authorize('manage-catalog');

        $this->validate([
            'code' => ['required', 'alpha_dash', 'max:80', 'unique:honors,code'],
            'titleDe' => ['required', 'string', 'max:200'],
            'organisationId' => ['required', 'exists:organisations,id'],
            'level' => ['required', Rule::in(['regional', 'national', 'international'])],
            'region' => ['nullable', Rule::in(array_keys(config('ehrungsfinder.regions')))],
        ]);

        $honor = Honor::create([
            'code' => $this->code,
            'title' => ['de' => $this->titleDe],
            'organisation_id' => $this->organisationId,
            'level' => $this->level,
            'region' => $this->region ?: null,
            'sort' => (int) Honor::max('sort') + 10,
        ]);
        $honor->procedure()->create([]);

        $this->redirectRoute('admin.honors.edit', $honor, navigate: true);
    }
}; ?>

@use('App\Support\Labels')

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="xl" level="1">{{ __('Ehrungen & Regeln') }}</flux:heading>
        @can('manage-catalog')
            <flux:modal.trigger name="create-honor">
                <flux:button icon="plus" variant="primary">{{ __('Ehrung anlegen') }}</flux:button>
            </flux:modal.trigger>
        @endcan
    </div>

    <div class="grid gap-3 md:grid-cols-2">
        @foreach ($this->honors as $honor)
            <a href="{{ route('admin.honors.edit', $honor) }}" wire:navigate class="flex flex-col gap-2 rounded-xl border border-zinc-200 p-4 hover:border-accent dark:border-zinc-700">
                <span class="font-semibold">{{ $honor->tr('title') }}</span>
                <span class="text-sm text-zinc-500">{{ Labels::level($honor->level) }} · {{ $honor->organisation->tr('name') }} · <code>{{ $honor->code }}</code></span>
                <span class="flex flex-wrap gap-2">
                    @if ($honor->publishedRule)
                        <flux:badge size="sm" color="green">{{ $honor->publishedRule->label() }}</flux:badge>
                        <flux:badge size="sm">{{ Labels::basis($honor->publishedRule->basis) }}</flux:badge>
                    @else
                        <flux:badge size="sm" color="violet">{{ __('Keine freigegebene Regelversion') }}</flux:badge>
                    @endif
                    @unless ($honor->active)<flux:badge size="sm" color="zinc">{{ __('inaktiv') }}</flux:badge>@endunless
                </span>
            </a>
        @endforeach
    </div>

    <flux:modal name="create-honor" class="md:w-[32rem]">
        <form wire:submit="create" class="flex flex-col gap-4">
            <flux:heading size="lg">{{ __('Ehrung anlegen') }}</flux:heading>
            <flux:input wire:model="titleDe" :label="__('Offizieller Name (Deutsch)')" required />
            <flux:input wire:model="code" :label="__('Code')" :description="__('Stabile ID, z. B. wj-st-silberne-ehrennadel. Später nicht änderbar.')" required />
            <flux:select wire:model="organisationId" :label="__('Verleihende Organisation')" :placeholder="__('Bitte wählen')">
                @foreach ($this->organisations as $org)
                    <flux:select.option :value="$org->id">{{ $org->tr('name') }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="level" :label="__('Ebene')">
                <flux:select.option value="regional">{{ __('Landesebene') }}</flux:select.option>
                <flux:select.option value="national">{{ __('Bundesebene') }}</flux:select.option>
                <flux:select.option value="international">{{ __('Internationale Ebene') }}</flux:select.option>
            </flux:select>
            <flux:select wire:model="region" :label="__('Nur für Landesverband')" :placeholder="__('alle')">
                <flux:select.option value="">{{ __('alle') }}</flux:select.option>
                @foreach (config('ehrungsfinder.regions') as $code => $name)
                    <flux:select.option :value="$code">{{ $name }}</flux:select.option>
                @endforeach
            </flux:select>
            <div class="flex justify-end"><flux:button type="submit" variant="primary">{{ __('Anlegen') }}</flux:button></div>
        </form>
    </flux:modal>
</div>
