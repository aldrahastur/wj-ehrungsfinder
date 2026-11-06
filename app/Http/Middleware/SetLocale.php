<?php

namespace App\Http\Middleware;

use App\Support\Localized;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Oberflächensprache aus der Sitzung (Sprachwahl), sonst Deutsch.
 * Die Sprache ist unabhängig vom gewählten Mitgliedsverband.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locales = Localized::locales();
        $locale = $request->session()->get('locale') ?? config('app.locale');

        app()->setLocale(in_array($locale, $locales, true) ? $locale : Localized::FALLBACK);

        return $next($request);
    }
}
