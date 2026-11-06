<?php

use App\Models\Feedback;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Rückmeldungen')] class extends Component {
    #[Url]
    public string $status = 'open';

    #[Computed]
    public function items()
    {
        return Feedback::query()->with('assessment')->when($this->status !== 'all', fn ($q) => $q->where('status', $this->status))->latest()->limit(200)->get();
    }

    public function toggle(int $id): void
    {
        $item = Feedback::findOrFail($id);
        $item->update(['status' => $item->status === 'open' ? 'done' : 'open']);
        unset($this->items);
    }
}; ?>

@use('App\Support\Labels')

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="xl" level="1">{{ __('Rückmeldungen') }}</flux:heading>
        <flux:select wire:model.live="status" class="max-w-48">
            <flux:select.option value="open">{{ __('Offen') }}</flux:select.option>
            <flux:select.option value="done">{{ __('Erledigt') }}</flux:select.option>
            <flux:select.option value="all">{{ __('Alle') }}</flux:select.option>
        </flux:select>
    </div>

    @forelse ($this->items as $item)
        <div class="flex flex-col gap-2 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700" wire:key="f-{{ $item->id }}">
            <p class="whitespace-pre-line">{{ $item->message }}</p>
            <div class="flex flex-wrap items-center justify-between gap-2 text-sm text-zinc-500">
                <span>
                    {{ $item->created_at->isoFormat('LLL') }}
                    @if ($item->assessment)
                        · {{ Labels::region($item->assessment->region) }} · {{ Labels::group($item->assessment->recipient_group) }}
                        · <flux:link :href="route('result.show', $item->assessment)" target="_blank">{{ __('Ergebnis ansehen') }}</flux:link>
                    @endif
                </span>
                <flux:button size="xs" wire:click="toggle({{ $item->id }})">{{ $item->status === 'open' ? __('Als erledigt markieren') : __('Wieder öffnen') }}</flux:button>
            </div>
        </div>
    @empty
        <flux:text>{{ __('Keine Rückmeldungen.') }}</flux:text>
    @endforelse
</div>
