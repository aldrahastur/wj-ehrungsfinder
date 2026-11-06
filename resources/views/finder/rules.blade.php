@use('App\Domain\Ehrungen\Evaluator')
@use('App\Support\Labels')
<x-layouts::public :title="__('Regeln & Quellen')">
    <div class="flex flex-col gap-6">
        <div>
            <flux:heading size="xl" level="1">{{ __('Regeln & Quellen') }}</flux:heading>
            <flux:text class="mt-2 max-w-prose">{{ __('Hier siehst du, welche persönlichen Ehrungen hinterlegt sind, nach welchen Kriterien geprüft wird und auf welchen Quellen die Regeln beruhen. Kreiseigene Ehrungen sowie Auszeichnungen für Projekte, Kreise und Unternehmen gehören nicht zum Umfang.') }}</flux:text>
        </div>

        @foreach ($honors as $honor)
            @php($version = $honor->publishedRule)
            <section class="grid gap-4 rounded-xl border border-zinc-200 bg-white p-5 md:grid-cols-2 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex flex-col gap-2">
                    <flux:heading size="lg" level="2">{{ $honor->tr('title') }}</flux:heading>
                    <flux:text>{{ Labels::level($honor->level) }} · {{ $honor->organisation->tr('name') }}@if ($honor->region) · {{ Labels::region($honor->region) }}@endif</flux:text>
                    @if ($honor->tr('scope'))<p class="text-sm">{{ $honor->tr('scope') }}</p>@endif
                    @if ($version)
                        <div class="flex flex-wrap gap-2">
                            <flux:badge size="sm">{{ Labels::basis($version->basis) }}</flux:badge>
                            <flux:badge size="sm" color="zinc">{{ __('Regelversion') }} {{ $version->label() }}</flux:badge>
                            @if ($version->valid_from)<flux:badge size="sm" color="zinc">{{ __('gültig ab') }} {{ $version->valid_from->isoFormat('L') }}</flux:badge>@endif
                        </div>
                        @if ($version->tr('basis_note'))<p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $version->tr('basis_note') }}</p>@endif
                        <ul class="list-disc ps-4 text-sm">
                            @foreach ($version->sources as $source)
                                <li><a class="underline underline-offset-2" href="{{ $source->url }}" target="_blank" rel="noopener">{{ $source->code }}: {{ $source->title }}</a></li>
                            @endforeach
                        </ul>
                    @else
                        <flux:badge size="sm" color="violet">{{ __('Keine freigegebene Regelversion') }}</flux:badge>
                    @endif
                </div>
                <div class="text-sm">
                    @if ($version?->criteria)
                        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ __('Geprüfte Kriterien') }}</p>
                        <ul class="list-disc ps-4">
                            @foreach (Evaluator::questionCodes($version->criteria) as $code)
                                <li>{{ Labels::questionCriterion($code) }}</li>
                            @endforeach
                        </ul>
                    @else
                        <flux:text>{{ __('Für diese Ehrung gibt es noch keine automatische Eignungsprüfung.') }}</flux:text>
                    @endif
                </div>
            </section>
        @endforeach
    </div>
</x-layouts::public>
