<?php

use App\Models\Honor;
use App\Models\Question;
use App\Models\ReferenceCase;
use App\Models\RuleVersion;
use App\Services\Ehrungsfinder\ReferenceCaseRunner;
use Database\Seeders\CatalogSeeder;

test('seeds catalog with one published version per honor', function () {
    $this->seed(CatalogSeeder::class);

    expect(Honor::count())->toBe(5)
        ->and(Question::count())->toBe(18)
        ->and(RuleVersion::where('status', RuleVersion::STATUS_PUBLISHED)->count())->toBe(5);

    Honor::all()->each(
        fn (Honor $h) => expect($h->publishedRule->sources()->exists())->toBeTrue("{$h->code} ohne Quelle")
    );
});

test('seeder is idempotent', function () {
    $this->seed(CatalogSeeder::class);
    $this->seed(CatalogSeeder::class);

    expect(RuleVersion::count())->toBe(5)
        ->and(ReferenceCase::count())->toBe(5);
});

test('all reference cases pass with published rules', function () {
    $this->seed(CatalogSeeder::class);

    $failed = app(ReferenceCaseRunner::class)->run()->where('passed', false);

    expect($failed)->toHaveCount(
        0,
        $failed->map(fn ($r) => $r['case']->name.': '.json_encode($r['mismatches']))->implode("\n"),
    );
});
