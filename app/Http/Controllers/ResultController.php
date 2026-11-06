<?php

namespace App\Http\Controllers;

use App\Domain\Ehrungen\RecipientGroup;
use App\Models\Assessment;
use App\Models\RuleVersion;
use App\Services\Ehrungsfinder\Catalog;
use App\Services\Ehrungsfinder\Finder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

/**
 * Druck- und Downloadansicht eines gespeicherten, anonymen Ergebnisses.
 * Ausgewertet wird mit den Regelversionen, die zum Zeitpunkt der Prüfung galten.
 */
class ResultController extends Controller
{
    public function __construct(private readonly Finder $finder, private readonly Catalog $catalog) {}

    public function show(Assessment $assessment): View
    {
        return view('finder.result-print', $this->data($assessment) + ['standalone' => false]);
    }

    public function download(Assessment $assessment): Response
    {
        $html = view('finder.result-print', $this->data($assessment) + ['standalone' => true])->render();
        $name = 'ehrungsfinder-ergebnis-'.$assessment->created_at->format('Y-m-d').'.html';

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$name.'"',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function data(Assessment $assessment): array
    {
        $versionIds = collect($assessment->results)->pluck('rule_version_id')->filter()->all();
        $overrides = RuleVersion::query()->with('sources')->whereIn('id', $versionIds)->get()->keyBy('honor_id')->all();
        $group = RecipientGroup::from($assessment->recipient_group);

        return [
            'assessment' => $assessment,
            'results' => $this->finder->assess($assessment->region, $group, $assessment->answers, $overrides),
            'answers' => $this->finder->effectiveAnswers($assessment->region, $group, $assessment->answers),
            'questions' => $this->catalog->questions(),
        ];
    }
}
