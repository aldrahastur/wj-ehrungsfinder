@use('App\Support\Labels')
@use('App\Support\Localized')
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ config('app.name') }} – {{ __('Ergebnis der Vorprüfung') }}</title>
    {{-- Eigenständige Stile, damit die heruntergeladene Datei ohne Server funktioniert --}}
    <style>
        :root { --ink: #1b2433; --muted: #5b6575; --line: #dde1e8; --accent: #23407a; --brass: #9a7322; }
        * { box-sizing: border-box; }
        body { margin: 0; font: 15px/1.55 system-ui, -apple-system, "Segoe UI", sans-serif; color: var(--ink); background: #fff; }
        main { max-width: 820px; margin: 0 auto; padding: 32px 20px 48px; }
        h1 { font: 600 1.6rem Georgia, "Times New Roman", serif; margin: 0 0 4px; }
        h2 { font: 600 1.15rem Georgia, serif; margin: 0; }
        .muted { color: var(--muted); }
        .toolbar { display: flex; gap: 8px; margin: 16px 0; }
        .toolbar button, .toolbar a { font: inherit; padding: 8px 14px; border: 1px solid var(--line); border-radius: 8px; background: #fff; color: var(--ink); text-decoration: none; cursor: pointer; }
        .toolbar .primary { background: var(--accent); border-color: var(--accent); color: #fff; }
        .card { border: 1px solid var(--line); border-top: 4px solid var(--line); border-radius: 10px; padding: 16px 18px; margin: 14px 0; break-inside: avoid; }
        .card.suitable { border-top-color: #1f7a4d; } .card.possible { border-top-color: #b07a00; }
        .card.not_suitable { border-top-color: #8b3a3a; } .card.clarification { border-top-color: #5a3f8f; } .card.info { border-top-color: var(--accent); }
        .state { display: inline-block; font-size: .8rem; font-weight: 600; padding: 2px 10px; border-radius: 999px; background: #eef1f6; }
        ul.criteria { list-style: none; padding: 0; margin: 6px 0; } ul.criteria ul { list-style: none; padding-left: 16px; border-left: 2px solid var(--line); margin-left: 6px; }
        .box { background: #f6f7f9; border-radius: 8px; padding: 8px 12px; margin-top: 8px; }
        .label { font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); font-weight: 600; }
        table { width: 100%; border-collapse: collapse; font-size: .9rem; } td, th { text-align: left; border-bottom: 1px solid var(--line); padding: 6px 4px; vertical-align: top; }
        a { color: var(--accent); }
        @media print { .toolbar { display: none; } main { padding-top: 0; } }
    </style>
</head>
<body>
<main>
    <p class="label" style="color: var(--brass)">{{ config('app.name') }}</p>
    <h1>{{ __('Ergebnis der Vorprüfung') }}</h1>
    <p class="muted">
        {{ __('Landesverband') }} {{ Labels::region($assessment->region) }} · {{ Labels::group($assessment->recipient_group) }} ·
        {{ __('erstellt am') }} {{ $assessment->updated_at->isoFormat('LLL') }}
    </p>

    @unless ($standalone)
        <div class="toolbar">
            <button type="button" class="primary" onclick="window.print()">{{ __('Drucken / als PDF speichern') }}</button>
            <a href="{{ route('result.download', $assessment) }}">{{ __('Herunterladen') }}</a>
            <a href="{{ route('home') }}">{{ __('Neue Prüfung') }}</a>
        </div>
    @endunless

    @foreach ($results as $result)
        @php($a = $result->assessment)
        <section class="card {{ $a->state->value }}">
            <span class="state">{{ Labels::state($a->state) }}</span>
            <h2 style="margin-top: 6px">{{ $result->honor->tr('title') }}</h2>
            <p class="muted" style="margin: 2px 0 6px">{{ Labels::level($result->honor->level) }} · {{ $result->honor->organisation->tr('name') }}</p>
            <p style="margin: 0">{{ Labels::stateExplanation($a->state) }}</p>

            @if ($reason = Labels::reason($a->reason, $a->reasonDetail))
                <div class="box"><span class="label">{{ __('Grund für die Klärung') }}</span><br>{{ $reason }}</div>
            @endif

            @if ($a->tree)
                <ul class="criteria">
                    @include('finder.partials.print-node', ['node' => $a->tree])
                </ul>
            @endif

            @if ($next = $result->honor->procedure?->tr('next_step'))
                <div class="box"><span class="label">{{ __('Nächster Schritt') }}</span><br>{{ $next }}
                    @if ($result->honor->procedure->application_url)
                        <br><a href="{{ $result->honor->procedure->application_url }}">{{ __('Zum offiziellen Antrag') }}</a>
                    @endif
                </div>
            @endif

            @if ($result->ruleVersion)
                <div class="box">
                    <span class="label">{{ __('Quellenstand') }}</span><br>
                    {{ Labels::basis($result->ruleVersion->basis) }} · {{ __('Regelversion') }} {{ $result->ruleVersion->label() }}
                    @foreach ($result->ruleVersion->sources as $source)
                        <br><a href="{{ $source->url }}">{{ $source->code }}: {{ $source->title }}</a>
                    @endforeach
                </div>
            @endif
        </section>
    @endforeach

    <h2 style="margin-top: 28px">{{ __('Deine Angaben') }}</h2>
    <table>
        @foreach ($questions as $q)
            @continue(! isset($answers[$q->code]))
            <tr><td>{{ $q->tr('text') }}</td><td style="white-space: nowrap">{{ Labels::answer($answers[$q->code]) }}</td></tr>
        @endforeach
    </table>

    <p class="muted" style="margin-top: 24px; font-size: .85rem">
        {{ __('Diese Vorprüfung ist eine Empfehlung auf Grundlage deiner Angaben und der hinterlegten Regeln. Über die Verleihung entscheiden die zuständigen Verbandsgremien. Qualitative Angaben sind Einschätzungen der vorschlagenden Person und werden mit Nachweisen geprüft.') }}
        @unless ($standalone)
            <br>{{ __('Link zu dieser Übersicht') }}: <a href="{{ route('result.show', $assessment) }}">{{ route('result.show', $assessment) }}</a>
        @endunless
    </p>
</main>
</body>
</html>
