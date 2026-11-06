<?php

namespace Tests\Feature\Ehrungsfinder;

use App\Models\Honor;
use App\Models\Question;
use App\Models\ReferenceCase;
use App\Models\RuleVersion;
use App\Services\Ehrungsfinder\ReferenceCaseRunner;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_catalog_with_one_published_version_per_honor(): void
    {
        $this->seed(CatalogSeeder::class);

        $this->assertSame(5, Honor::count());
        $this->assertSame(18, Question::count());
        $this->assertSame(5, RuleVersion::where('status', RuleVersion::STATUS_PUBLISHED)->count());
        Honor::all()->each(fn (Honor $h) => $this->assertTrue($h->publishedRule->sources()->exists(), "{$h->code} ohne Quelle"));
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(CatalogSeeder::class);
        $this->seed(CatalogSeeder::class);

        $this->assertSame(5, RuleVersion::count());
        $this->assertSame(5, ReferenceCase::count());
    }

    public function test_all_reference_cases_pass_with_published_rules(): void
    {
        $this->seed(CatalogSeeder::class);

        $failed = app(ReferenceCaseRunner::class)->run()->where('passed', false);

        $this->assertCount(0, $failed, $failed->map(fn ($r) => $r['case']->name.': '.json_encode($r['mismatches']))->implode("\n"));
    }
}
