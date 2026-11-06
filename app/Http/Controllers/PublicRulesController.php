<?php

namespace App\Http\Controllers;

use App\Services\Ehrungsfinder\Catalog;
use Illuminate\Contracts\View\View;

/**
 * Öffentliche Übersicht: Katalog, Kriterien, Regelgrundlage und Quellen.
 */
class PublicRulesController extends Controller
{
    public function __invoke(Catalog $catalog): View
    {
        return view('finder.rules', [
            'honors' => $catalog->honors(),
            'questions' => $catalog->questions(),
        ]);
    }
}
