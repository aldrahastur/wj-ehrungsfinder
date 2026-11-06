<?php

use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PublicRulesController;
use App\Http\Controllers\ResultController;
use Illuminate\Support\Facades\Route;

// Öffentlicher Frageassistent (ohne Anmeldung, anonym)
Route::livewire('/', 'pages::finder.index')->name('home');
Route::get('regeln', PublicRulesController::class)->name('rules');
Route::get('ergebnis/{assessment}', [ResultController::class, 'show'])->name('result.show');
Route::get('ergebnis/{assessment}/download', [ResultController::class, 'download'])->name('result.download');
Route::get('sprache/{locale}', LocaleController::class)->name('locale')->where('locale', '[a-z]{2}');

// Verwaltungsbereich
Route::prefix('verwaltung')->middleware(['auth', 'verified', 'can:access-admin'])->group(function () {
    Route::livewire('/', 'pages::admin.dashboard')->name('dashboard');
    Route::livewire('ehrungen', 'pages::admin.honors.index')->name('admin.honors.index');
    Route::livewire('ehrungen/{honor}', 'pages::admin.honors.edit')->name('admin.honors.edit');
    Route::livewire('regelversionen/{ruleVersion}', 'pages::admin.rules.show')->name('admin.rules.show');
    Route::livewire('fragen', 'pages::admin.questions')->name('admin.questions');
    Route::livewire('referenzfaelle', 'pages::admin.reference-cases')->name('admin.reference-cases');
    Route::livewire('quellen', 'pages::admin.sources')->name('admin.sources');
    Route::livewire('rueckmeldungen', 'pages::admin.feedback')->name('admin.feedback');
    Route::livewire('personen', 'pages::admin.users')->middleware('can:manage-catalog')->name('admin.users');
});

require __DIR__.'/settings.php';
