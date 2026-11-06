<?php

use App\Models\Honor;
use App\Models\Organisation;
use App\Models\RuleVersion;
use App\Models\User;
use App\Services\Ehrungsfinder\Catalog;
use App\Services\Ehrungsfinder\RuleWorkflow;
use Database\Seeders\CatalogSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(CatalogSeeder::class);

    $this->honor = Honor::firstWhere('code', 'wjd-silberne-juniorennadel');
    $wjd = Organisation::firstWhere('code', 'wjd');
    $this->editor = User::factory()->create();
    $this->editor->organisations()->attach($wjd, ['role' => User::ROLE_EDITOR]);
    $this->approver = User::factory()->create();
    $this->approver->organisations()->attach($wjd, ['role' => User::ROLE_APPROVER]);
    $this->workflow = app(RuleWorkflow::class);
});

test('draft copies published version', function () {
    $draft = $this->workflow->createDraft($this->honor, $this->editor);

    expect($draft->version)->toBe(2)
        ->and($draft->status)->toBe(RuleVersion::STATUS_DRAFT)
        ->and($draft->criteria)->toEqual($this->honor->publishedRule->criteria)
        ->and($draft->sources()->count())->toBe(2);
});

test('approval publishes and supersedes previous version', function () {
    $draft = $this->workflow->createDraft($this->honor, $this->editor);
    $draft->update(['basis' => 'confirmed']);

    $this->workflow->submit($draft, $this->editor);
    $this->workflow->approve($draft->fresh(), $this->approver->fresh(), 'Fachkontakt WJD');

    expect($draft->fresh()->status)->toBe(RuleVersion::STATUS_PUBLISHED)
        ->and(RuleVersion::where('honor_id', $this->honor->id)->where('version', 1)->value('status'))->toBe(RuleVersion::STATUS_SUPERSEDED)
        ->and(RuleVersion::where('honor_id', $this->honor->id)->where('status', RuleVersion::STATUS_PUBLISHED)->count())->toBe(1)
        ->and($draft->fresh()->approval_role)->toBe('Fachkontakt WJD');
});

test('editor cannot approve', function () {
    $draft = $this->workflow->createDraft($this->honor, $this->editor);
    $this->workflow->submit($draft, $this->editor);

    $this->workflow->approve($draft->fresh(), $this->editor->fresh(), 'Redaktion');
})->throws(ValidationException::class);

test('four eyes principle', function () {
    $draft = $this->workflow->createDraft($this->honor, $this->approver);
    $this->workflow->submit($draft, $this->approver);

    $this->workflow->approve($draft->fresh(), $this->approver->fresh(), 'Freigabe');
})->throws(ValidationException::class);

test('submission requires a source', function () {
    $draft = $this->workflow->createDraft($this->honor, $this->editor);
    $draft->sources()->detach();

    $this->workflow->submit($draft, $this->editor);
})->throws(ValidationException::class);

test('invalid criteria cannot be submitted', function () {
    $draft = $this->workflow->createDraft($this->honor, $this->editor);
    $draft->update(['criteria' => ['question' => 'q_gibt_es_nicht']]);

    $this->workflow->submit($draft, $this->editor);
})->throws(ValidationException::class);

test('failing reference cases block approval', function () {
    $draft = $this->workflow->createDraft($this->honor, $this->editor);
    // Projekt UND Training verlangen: Referenzfall 1 (Projekt ja, Training unbekannt) erwartet aber „passend“.
    $draft->update(['criteria' => ['all' => [['question' => 'q_kreisprojekt'], ['question' => 'q_training']]]]);
    $this->workflow->submit($draft, $this->editor);

    try {
        $this->workflow->approve($draft->fresh(), $this->approver->fresh(), 'Fachkontakt WJD');
        $this->fail('Freigabe hätte blockiert werden müssen.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('reference_cases');
    }

    expect($draft->fresh()->status)->toBe(RuleVersion::STATUS_IN_REVIEW);
});

test('rejection returns to draft with note', function () {
    $draft = $this->workflow->createDraft($this->honor, $this->editor);
    $this->workflow->submit($draft, $this->editor);
    $this->workflow->reject($draft->fresh(), $this->approver->fresh(), 'Quelle Q2 ist veraltet.');

    expect($draft->fresh()->status)->toBe(RuleVersion::STATUS_DRAFT)
        ->and($draft->events()->where('action', 'rejected')->value('note'))->toBe('Quelle Q2 ist veraltet.');
});

test('drafts do not affect public results', function () {
    $draft = $this->workflow->createDraft($this->honor, $this->editor);
    $draft->update(['criteria' => ['blocked' => ['de' => 'Test']]]);

    app(Catalog::class)->flush();

    expect(app(Catalog::class)->honors()->firstWhere('code', $this->honor->code)->publishedRule->id)
        ->toBe($this->honor->publishedRule->id);
});
