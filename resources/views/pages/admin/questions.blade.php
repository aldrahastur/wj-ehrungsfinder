<?php

use App\Domain\Ehrungen\Evaluator;
use App\Models\Question;
use App\Models\RuleVersion;
use App\Support\Localized;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Fragen')] class extends Component {
    public ?int $editingId = null;

    public string $code = '';

    /** @var array<string, array<string, string>> */
    public array $texts = [];

    public ?string $region = null;

    public int $sort = 0;

    public bool $active = true;

    #[Computed]
    public function questions()
    {
        return Question::query()->ordered()->get();
    }

    /**
     * Welche veröffentlichten Regeln eine Frage verwenden.
     *
     * @return array<string, list<string>>
     */
    #[Computed]
    public function usage(): array
    {
        $usage = [];
        RuleVersion::query()->with('honor')->where('status', RuleVersion::STATUS_PUBLISHED)->whereNotNull('criteria')->get()
            ->each(function (RuleVersion $v) use (&$usage) {
                foreach (Evaluator::questionCodes($v->criteria) as $code) {
                    $usage[$code][] = $v->honor->tr('title');
                }
            });

        return $usage;
    }

    public function edit(?int $id = null): void
    {
        Gate::authorize('manage-catalog');

        $q = $id ? Question::findOrFail($id) : new Question(['sort' => (int) Question::max('sort') + 10]);
        $this->editingId = $q->id;
        $this->code = (string) $q->code;
        foreach (['text', 'criterion', 'explanation'] as $field) {
            foreach (Localized::locales() as $locale) {
                $this->texts[$field][$locale] = $q->{$field}[$locale] ?? '';
            }
        }
        $this->region = $q->region;
        $this->sort = (int) $q->sort;
        $this->active = $q->active ?? true;
        $this->resetValidation();

        Flux::modal('question-form')->show();
    }

    public function save(): void
    {
        Gate::authorize('manage-catalog');

        $this->validate([
            'code' => $this->editingId ? [] : ['required', 'regex:/^q_[a-z0-9_]+$/', 'unique:questions,code'],
            'texts.text.de' => ['required', 'string', 'max:500'],
            'texts.criterion.de' => ['required', 'string', 'max:300'],
            'texts.*.*' => ['nullable', 'string', 'max:2000'],
            'region' => ['nullable', Rule::in(array_keys(config('ehrungsfinder.regions')))],
            'sort' => ['integer'],
        ], ['code.regex' => __('Der Code beginnt mit q_ und enthält nur Kleinbuchstaben, Ziffern und Unterstriche.')]);

        $clean = fn (array $v) => array_filter($v, 'filled') ?: null;
        $data = [
            'text' => $clean($this->texts['text']),
            'criterion' => $clean($this->texts['criterion']),
            'explanation' => $clean($this->texts['explanation']),
            'region' => $this->region ?: null,
            'sort' => $this->sort,
            'active' => $this->active,
        ];

        // Der Code ist eine stabile ID und wird nach dem Anlegen nicht mehr geändert.
        $this->editingId
            ? Question::findOrFail($this->editingId)->update($data)
            : Question::create($data + ['code' => $this->code]);

        Flux::modal('question-form')->close();
        unset($this->questions);
        Flux::toast(variant: 'success', text: __('Frage gespeichert.'));
    }
}; ?>

@use('App\Support\Labels')

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl" level="1">{{ __('Fragen') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Die Reihenfolge bestimmt, in welcher Folge der Assistent fragt. Gestellt werden nur Fragen, die das Ergebnis noch verändern können.') }}</flux:text>
        </div>
        @can('manage-catalog')
            <flux:button icon="plus" variant="primary" wire:click="edit">{{ __('Frage anlegen') }}</flux:button>
        @endcan
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-xs text-zinc-500 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-2 text-start">#</th>
                    <th class="px-4 py-2 text-start">{{ __('Frage') }}</th>
                    <th class="px-4 py-2 text-start">{{ __('Verwendet in') }}</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($this->questions as $q)
                    <tr class="border-t border-zinc-200 align-top dark:border-zinc-700" wire:key="q-{{ $q->id }}">
                        <td class="px-4 py-2 tabular-nums text-zinc-500">{{ $q->sort }}</td>
                        <td class="px-4 py-2">
                            {{ $q->tr('text') }}
                            <div class="mt-1 flex flex-wrap gap-1">
                                <flux:badge size="sm" color="zinc"><code>{{ $q->code }}</code></flux:badge>
                                @if ($q->region)<flux:badge size="sm">{{ __('nur') }} {{ Labels::region($q->region) }}</flux:badge>@endif
                                @if ($q->implied_by)<flux:badge size="sm" color="blue">{{ __('mit Ableitung') }}</flux:badge>@endif
                                @unless ($q->active)<flux:badge size="sm" color="red">{{ __('inaktiv') }}</flux:badge>@endunless
                            </div>
                        </td>
                        <td class="px-4 py-2 text-zinc-600 dark:text-zinc-400">{{ implode(', ', $this->usage[$q->code] ?? []) ?: '–' }}</td>
                        <td class="px-4 py-2 text-end">
                            @can('manage-catalog')
                                <flux:button size="xs" variant="ghost" wire:click="edit({{ $q->id }})">{{ __('Bearbeiten') }}</flux:button>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <flux:modal name="question-form" class="md:w-[44rem]">
        <form wire:submit="save" class="flex flex-col gap-4">
            <flux:heading size="lg">{{ $editingId ? __('Frage bearbeiten') : __('Frage anlegen') }}</flux:heading>
            <flux:input wire:model="code" :label="__('Code (stabile ID)')" :disabled="(bool) $editingId" placeholder="q_beispiel" />
            @foreach (['text' => __('Frage'), 'criterion' => __('Kriterium auf der Ergebniskarte'), 'explanation' => __('Erläuterung mit Beispielen')] as $field => $label)
                <div class="grid gap-3 md:grid-cols-2">
                    @foreach (Localized::locales() as $locale)
                        <flux:textarea wire:model="texts.{{ $field }}.{{ $locale }}" rows="2" :label="$label.' ('.strtoupper($locale).')'" />
                    @endforeach
                </div>
            @endforeach
            <div class="grid gap-3 md:grid-cols-3">
                <flux:select wire:model="region" :label="__('Nur für Landesverband')">
                    <flux:select.option value="">{{ __('alle') }}</flux:select.option>
                    @foreach (config('ehrungsfinder.regions') as $code => $name)
                        <flux:select.option :value="$code">{{ $name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="sort" type="number" :label="__('Reihenfolge')" />
                <div class="pt-7"><flux:switch wire:model="active" :label="__('Aktiv')" /></div>
            </div>
            <div class="flex justify-end"><flux:button type="submit" variant="primary">{{ __('Speichern') }}</flux:button></div>
        </form>
    </flux:modal>
</div>
