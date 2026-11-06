<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased dark:bg-zinc-900 dark:text-zinc-100">
        <header class="border-b border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800" data-print-hide>
            <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3 px-4 py-3">
                <a href="{{ route('home') }}" class="flex items-center gap-3" wire:navigate>
                    <span class="flex size-9 items-center justify-center rounded-full border-2 border-brass text-brass">
                        <x-app-logo-icon class="size-5" />
                    </span>
                    <span class="leading-tight">
                        <span class="block font-semibold">{{ config('app.name') }}</span>
                        <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ __('Persönliche Ehrungen der Wirtschaftsjunioren') }}</span>
                    </span>
                </a>

                <nav class="flex items-center gap-1" aria-label="{{ __('Hauptnavigation') }}">
                    <flux:button variant="ghost" size="sm" :href="route('home')" wire:navigate>{{ __('Prüfung') }}</flux:button>
                    <flux:button variant="ghost" size="sm" :href="route('rules')" wire:navigate>{{ __('Regeln & Quellen') }}</flux:button>
                    <flux:dropdown align="end">
                        <flux:button variant="ghost" size="sm" icon="language" icon:trailing="chevron-down">{{ strtoupper(app()->getLocale()) }}</flux:button>
                        <flux:menu>
                            <flux:menu.item :href="route('locale', 'de')">Deutsch</flux:menu.item>
                            <flux:menu.item :href="route('locale', 'en')">English</flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </nav>
            </div>
        </header>

        @if (config('ehrungsfinder.pilot_mode'))
            <div class="mx-auto mt-4 max-w-5xl px-4" data-print-hide>
                <flux:callout icon="beaker" color="amber" :heading="__('Pilotbetrieb')">
                    <flux:callout.text>{{ __('Ein Teil der Regeln ist recherchiert und noch nicht vom Verband bestätigt. Solche Ergebnisse sind gekennzeichnet. Die Empfehlung bereitet eine Entscheidung vor; die zuständigen Gremien entscheiden über die Verleihung.') }}</flux:callout.text>
                </flux:callout>
            </div>
        @endif

        <main class="mx-auto max-w-5xl px-4 py-6 sm:py-8">
            {{ $slot }}
        </main>

        <footer class="mx-auto max-w-5xl px-4 pb-10 text-sm text-zinc-500 dark:text-zinc-400" data-print-hide>
            <div class="flex flex-wrap justify-between gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <span>{{ __('Anonyme Vorprüfung – es werden keine Namen oder Geburtsdaten erhoben.') }}</span>
                <a href="{{ route('login') }}" class="underline underline-offset-2">{{ __('Verwaltung') }}</a>
            </div>
        </footer>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
