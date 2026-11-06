<?php

namespace Tests\Feature\Ehrungsfinder;

use App\Models\Assessment;
use App\Models\Feedback;
use App\Models\ReferenceCase;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FinderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_start_page_renders(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('Welche persönlichen Ehrungen kommen infrage?');
    }

    public function test_full_run_saves_anonymous_result(): void
    {
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
        $this->assertTrue($assessment->completed);
        $this->assertSame('ST', $assessment->region);
        $this->assertCount(5, $assessment->results);
        $this->assertArrayNotHasKey('name', $assessment->answers);
    }

    public function test_changing_an_answer_recalculates_result(): void
    {
        $case = ReferenceCase::where('show_as_example', true)->orderBy('id')->first();

        $component = Livewire::test('pages::finder.index')->call('loadExample', $case->id)->assertSet('step', 'result');
        $before = collect(Assessment::sole()->results)->firstWhere('honor', 'wjd-silberne-juniorennadel')['state'];

        $component->call('editAnswer', 'q_kreisprojekt')->assertSet('step', 'question')
            ->call('answer', 'no')->assertSet('step', 'result');

        $component->call('editAnswer', 'q_training')->call('answer', 'no');
        $after = collect(Assessment::sole()->results)->firstWhere('honor', 'wjd-silberne-juniorennadel')['state'];

        $this->assertSame('suitable', $before);
        $this->assertSame('not_suitable', $after);
    }

    public function test_unknown_answer_is_allowed(): void
    {
        Livewire::test('pages::finder.index')->call('start')->call('answer', 'unknown')->assertSet('answers.q_kreisprojekt', 'unknown');
    }

    public function test_invalid_answer_is_rejected(): void
    {
        Livewire::test('pages::finder.index')->call('start')->call('answer', 'maybe')->assertStatus(422);
    }

    public function test_other_region_shows_notice_and_skips_state_questions(): void
    {
        $component = Livewire::test('pages::finder.index')->set('region', 'BY')->call('start');

        for ($i = 0; $i < 25 && $component->get('step') === 'question'; $i++) {
            $this->assertNotSame('q_lsa', $component->get('current'));
            $component->call('answer', 'no');
        }

        $component->assertSee('noch nicht erfasst');
    }

    public function test_feedback_is_stored_with_assessment(): void
    {
        $case = ReferenceCase::where('show_as_example', true)->first();

        Livewire::test('pages::finder.index')
            ->call('loadExample', $case->id)
            ->set('feedbackMessage', 'Die Frage zur Botschafterrolle war unklar.')
            ->call('sendFeedback')
            ->assertSet('feedbackSent', true);

        $this->assertSame(Assessment::sole()->id, Feedback::sole()->assessment_id);
    }

    public function test_print_view_and_download(): void
    {
        $case = ReferenceCase::where('show_as_example', true)->first();
        Livewire::test('pages::finder.index')->call('loadExample', $case->id);
        $assessment = Assessment::sole();

        $this->get(route('result.show', $assessment))->assertOk()->assertSee('Silberne Juniorennadel');
        $this->get(route('result.download', $assessment))->assertOk()->assertHeader('Content-Disposition');
    }

    public function test_rules_page_lists_catalog_and_sources(): void
    {
        $this->get(route('rules'))->assertOk()->assertSee('JCI-Senatorenwürde')->assertSee('Q3');
    }

    public function test_language_can_be_switched_independently_of_region(): void
    {
        $this->get(route('locale', 'en'))->assertRedirect();
        $this->get(route('home'))->assertSee('Which personal honours could apply?');
    }
}
