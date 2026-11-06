<?php

namespace Tests\Feature\Ehrungsfinder;

use App\Models\Honor;
use App\Models\Organisation;
use App\Models\RuleVersion;
use App\Models\User;
use App\Services\Ehrungsfinder\Catalog;
use App\Services\Ehrungsfinder\RuleWorkflow;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RuleWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Honor $honor;

    private User $editor;

    private User $approver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);

        $this->honor = Honor::firstWhere('code', 'wjd-silberne-juniorennadel');
        $wjd = Organisation::firstWhere('code', 'wjd');
        $this->editor = User::factory()->create();
        $this->editor->organisations()->attach($wjd, ['role' => User::ROLE_EDITOR]);
        $this->approver = User::factory()->create();
        $this->approver->organisations()->attach($wjd, ['role' => User::ROLE_APPROVER]);
    }

    private function workflow(): RuleWorkflow
    {
        return app(RuleWorkflow::class);
    }

    public function test_draft_copies_published_version(): void
    {
        $draft = $this->workflow()->createDraft($this->honor, $this->editor);

        $this->assertSame(2, $draft->version);
        $this->assertSame(RuleVersion::STATUS_DRAFT, $draft->status);
        $this->assertEquals($this->honor->publishedRule->criteria, $draft->criteria);
        $this->assertSame(2, $draft->sources()->count());
    }

    public function test_approval_publishes_and_supersedes_previous_version(): void
    {
        $draft = $this->workflow()->createDraft($this->honor, $this->editor);
        $draft->update(['basis' => 'confirmed']);

        $this->workflow()->submit($draft, $this->editor);
        $this->workflow()->approve($draft->fresh(), $this->approver->fresh(), 'Fachkontakt WJD');

        $this->assertSame(RuleVersion::STATUS_PUBLISHED, $draft->fresh()->status);
        $this->assertSame(RuleVersion::STATUS_SUPERSEDED, RuleVersion::where('honor_id', $this->honor->id)->where('version', 1)->value('status'));
        $this->assertSame(1, RuleVersion::where('honor_id', $this->honor->id)->where('status', RuleVersion::STATUS_PUBLISHED)->count());
        $this->assertSame('Fachkontakt WJD', $draft->fresh()->approval_role);
    }

    public function test_editor_cannot_approve(): void
    {
        $draft = $this->workflow()->createDraft($this->honor, $this->editor);
        $this->workflow()->submit($draft, $this->editor);

        $this->expectException(ValidationException::class);
        $this->workflow()->approve($draft->fresh(), $this->editor->fresh(), 'Redaktion');
    }

    public function test_four_eyes_principle(): void
    {
        $draft = $this->workflow()->createDraft($this->honor, $this->approver);
        $this->workflow()->submit($draft, $this->approver);

        $this->expectException(ValidationException::class);
        $this->workflow()->approve($draft->fresh(), $this->approver->fresh(), 'Freigabe');
    }

    public function test_submission_requires_a_source(): void
    {
        $draft = $this->workflow()->createDraft($this->honor, $this->editor);
        $draft->sources()->detach();

        $this->expectException(ValidationException::class);
        $this->workflow()->submit($draft, $this->editor);
    }

    public function test_invalid_criteria_cannot_be_submitted(): void
    {
        $draft = $this->workflow()->createDraft($this->honor, $this->editor);
        $draft->update(['criteria' => ['question' => 'q_gibt_es_nicht']]);

        $this->expectException(ValidationException::class);
        $this->workflow()->submit($draft, $this->editor);
    }

    public function test_failing_reference_cases_block_approval(): void
    {
        $draft = $this->workflow()->createDraft($this->honor, $this->editor);
        // Projekt UND Training verlangen: Referenzfall 1 (Projekt ja, Training unbekannt) erwartet aber „passend“.
        $draft->update(['criteria' => ['all' => [['question' => 'q_kreisprojekt'], ['question' => 'q_training']]]]);
        $this->workflow()->submit($draft, $this->editor);

        try {
            $this->workflow()->approve($draft->fresh(), $this->approver->fresh(), 'Fachkontakt WJD');
            $this->fail('Freigabe hätte blockiert werden müssen.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('reference_cases', $e->errors());
        }

        $this->assertSame(RuleVersion::STATUS_IN_REVIEW, $draft->fresh()->status);
    }

    public function test_rejection_returns_to_draft_with_note(): void
    {
        $draft = $this->workflow()->createDraft($this->honor, $this->editor);
        $this->workflow()->submit($draft, $this->editor);
        $this->workflow()->reject($draft->fresh(), $this->approver->fresh(), 'Quelle Q2 ist veraltet.');

        $this->assertSame(RuleVersion::STATUS_DRAFT, $draft->fresh()->status);
        $this->assertSame('Quelle Q2 ist veraltet.', $draft->events()->where('action', 'rejected')->value('note'));
    }

    public function test_drafts_do_not_affect_public_results(): void
    {
        $draft = $this->workflow()->createDraft($this->honor, $this->editor);
        $draft->update(['criteria' => ['blocked' => ['de' => 'Test']]]);

        app(Catalog::class)->flush();

        $this->assertSame($this->honor->publishedRule->id, app(Catalog::class)->honors()->firstWhere('code', $this->honor->code)->publishedRule->id);
    }
}
