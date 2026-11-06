<?php

use App\Models\Assessment;
use App\Models\Feedback;
use App\Models\ReferenceCase;
use Database\Seeders\CatalogSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(CatalogSeeder::class);
});

test('start page renders', function () {
    $this->get(route('home'))->assertOk()->assertSee('Welche persönlichen Ehrungen kommen infrage?');
});

test('full run saves anonymous result', function () {
    $component = Livewire::test('pages::finder.index')
        ->set('region', 'ST')
        ->set('group', 'member')
        ->call('start')
        ->assertSet('step', 'question')
        ->assertSet('current', 'q_kreisprojekt');

    for ($i = 0; $i < 25 && $component->get('step') === 'question'; $i++) {
        $component->call('answer', 'yes');
    }

    $component->assertSet('step', 'result')->assertSee('Ergebnis der Vorprüfung');

    $assessment = Assessment::sole();

    expect($assessment->completed)->toBeTrue()
        ->and($assessment->region)->toBe('ST')
        ->and($assessment->results)->toHaveCount(5)
        ->and($assessment->answers)->not->toHaveKey('name');
});

test('changing an answer recalculates result', function () {
    $case = ReferenceCase::where('show_as_example', true)->orderBy('id')->first();

    $component = Livewire::test('pages::finder.index')->call('loadExample', $case->id)->assertSet('step', 'result');
    $before = collect(Assessment::sole()->results)->firstWhere('honor', 'wjd-silberne-juniorennadel')['state'];

    $component->call('editAnswer', 'q_kreisprojekt')->assertSet('step', 'question')
        ->call('answer', 'no')->assertSet('step', 'result');

    $component->call('editAnswer', 'q_training')->call('answer', 'no');
    $after = collect(Assessment::sole()->results)->firstWhere('honor', 'wjd-silberne-juniorennadel')['state'];

    expect($before)->toBe('suitable')
        ->and($after)->toBe('not_suitable');
});

test('unknown answer is allowed', function () {
    Livewire::test('pages::finder.index')->call('start')->call('answer', 'unknown')->assertSet('answers.q_kreisprojekt', 'unknown');
});

test('invalid answer is rejected', function () {
    Livewire::test('pages::finder.index')->call('start')->call('answer', 'maybe')->assertStatus(422);
});

test('other region shows notice and skips state questions', function () {
    $component = Livewire::test('pages::finder.index')->set('region', 'BY')->call('start');

    for ($i = 0; $i < 25 && $component->get('step') === 'question'; $i++) {
        expect($component->get('current'))->not->toBe('q_lsa');
        $component->call('answer', 'no');
    }

    $component->assertSee('noch nicht erfasst');
});

test('feedback is stored with assessment', function () {
    $case = ReferenceCase::where('show_as_example', true)->first();

    Livewire::test('pages::finder.index')
        ->call('loadExample', $case->id)
        ->set('feedbackMessage', 'Die Frage zur Botschafterrolle war unklar.')
        ->call('sendFeedback')
        ->assertSet('feedbackSent', true);

    expect(Feedback::sole()->assessment_id)->toBe(Assessment::sole()->id);
});

test('print view and download', function () {
    $case = ReferenceCase::where('show_as_example', true)->first();
    Livewire::test('pages::finder.index')->call('loadExample', $case->id);
    $assessment = Assessment::sole();

    $this->get(route('result.show', $assessment))->assertOk()->assertSee('Silberne Juniorennadel');
    $this->get(route('result.download', $assessment))->assertOk()->assertHeader('Content-Disposition');
});

test('rules page lists catalog and sources', function () {
    $this->get(route('rules'))->assertOk()->assertSee('JCI-Senatorenwürde')->assertSee('Q3');
});

test('language can be switched independently of region', function () {
    $this->get(route('locale', 'en'))->assertRedirect();
    $this->get(route('home'))->assertSee('Which personal honours could apply?');
});
