<?php

use App\Models\Organisation;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Personen & Rollen')] class extends Component {
    public string $name = '';

    public string $email = '';

    public string $password = '';

    /** @var array<int, array<int, string>> user_id → organisation_id → role */
    public array $roles = [];

    public function mount(): void
    {
        Gate::authorize('manage-catalog');
        $this->loadRoles();
    }

    #[Computed]
    public function users()
    {
        return User::query()->with('organisations')->orderBy('name')->get();
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12'],
        ]);

        $user = new User(['name' => $this->name, 'email' => $this->email, 'password' => $this->password]);
        $user->email_verified_at = now();
        $user->save();

        $this->reset('name', 'email', 'password');
        Flux::modal('user-form')->close();
        unset($this->users);
        $this->loadRoles();
        Flux::toast(variant: 'success', text: __('Zugang angelegt. Teile das Passwort auf einem sicheren Weg.'));
    }

    public function generatePassword(): void
    {
        $this->password = Str::password(16);
    }

    public function toggleAdmin(int $id): void
    {
        Gate::authorize('manage-catalog');
        abort_if($id === Auth::id(), 422, __('Du kannst dir die Adminrechte nicht selbst entziehen.'));

        $user = User::findOrFail($id);
        $user->forceFill(['is_admin' => ! $user->is_admin])->save();
        unset($this->users);
    }

    public function saveRoles(int $userId): void
    {
        Gate::authorize('manage-catalog');

        $sync = collect($this->roles[$userId] ?? [])
            ->filter(fn ($role) => in_array($role, [User::ROLE_EDITOR, User::ROLE_APPROVER], true))
            ->map(fn ($role) => ['role' => $role])
            ->all();

        User::findOrFail($userId)->organisations()->sync($sync);
        unset($this->users);
        Flux::toast(variant: 'success', text: __('Rollen gespeichert.'));
    }

    private function loadRoles(): void
    {
        $this->roles = [];
        foreach ($this->users as $user) {
            foreach ($this->organisations as $org) {
                $this->roles[$user->id][$org->id] = $user->roleFor($org) ?? '';
            }
        }
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl" level="1">{{ __('Personen & Rollen') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Rollen gelten je Regelpaket. Redaktion bearbeitet Entwürfe, Freigabe veröffentlicht sie. Hinterlege bei Vorstandswechseln die Rolle bei der Nachfolge.') }}</flux:text>
        </div>
        <flux:modal.trigger name="user-form"><flux:button icon="user-plus" variant="primary">{{ __('Zugang anlegen') }}</flux:button></flux:modal.trigger>
    </div>

    @foreach ($this->users as $user)
        <section class="flex flex-col gap-3 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700" wire:key="u-{{ $user->id }}">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p class="font-medium">{{ $user->name }} @if ($user->is_admin)<flux:badge size="sm" color="blue">Admin</flux:badge>@endif</p>
                    <p class="text-sm text-zinc-500">{{ $user->email }}</p>
                </div>
                @if ($user->id !== auth()->id())
                    <flux:button size="xs" wire:click="toggleAdmin({{ $user->id }})">{{ $user->is_admin ? __('Adminrechte entziehen') : __('Zum Admin machen') }}</flux:button>
                @endif
            </div>
            <div class="grid gap-3 sm:grid-cols-3">
                @foreach ($this->organisations as $org)
                    <flux:select wire:model="roles.{{ $user->id }}.{{ $org->id }}" :label="$org->tr('name')" size="sm">
                        <flux:select.option value="">{{ __('keine Rolle') }}</flux:select.option>
                        <flux:select.option value="editor">{{ __('Redaktion') }}</flux:select.option>
                        <flux:select.option value="approver">{{ __('Freigabe') }}</flux:select.option>
                    </flux:select>
                @endforeach
            </div>
            <div><flux:button size="sm" wire:click="saveRoles({{ $user->id }})">{{ __('Rollen speichern') }}</flux:button></div>
        </section>
    @endforeach

    <flux:modal name="user-form" class="md:w-[28rem]">
        <form wire:submit="create" class="flex flex-col gap-4">
            <flux:heading size="lg">{{ __('Zugang anlegen') }}</flux:heading>
            <flux:input wire:model="name" :label="__('Name')" />
            <flux:input wire:model="email" type="email" :label="__('E-Mail')" />
            <flux:input wire:model="password" :label="__('Startpasswort')" :description="__('Mindestens 12 Zeichen. Die Person kann es unter Einstellungen ändern.')" />
            <div class="flex justify-between">
                <flux:button type="button" size="sm" variant="ghost" wire:click="generatePassword">{{ __('Passwort erzeugen') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Anlegen') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
