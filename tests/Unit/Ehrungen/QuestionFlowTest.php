<?php

namespace Tests\Unit\Ehrungen;

use App\Domain\Ehrungen\QuestionFlow;
use App\Domain\Ehrungen\RecipientGroup;
use PHPUnit\Framework\TestCase;
use Tests\Support\CatalogFixture;

class QuestionFlowTest extends TestCase
{
    private function flow(string $region = 'ST', RecipientGroup $group = RecipientGroup::Member): QuestionFlow
    {
        return new QuestionFlow(CatalogFixture::questions(), CatalogFixture::rules(), $region, $group);
    }

    /**
     * @param  array<string, string>  $answers
     * @return list<string>
     */
    private function openCodes(QuestionFlow $flow, array $answers): array
    {
        return array_map(fn ($q) => $q->code, $flow->open($answers));
    }

    public function test_starts_with_first_question(): void
    {
        $this->assertSame('q_kreisprojekt', $this->flow()->next([])?->code);
    }

    public function test_no_international_engagement_ends_only_the_senator_path(): void
    {
        $open = $this->openCodes($this->flow(), ['q_international' => 'no']);

        $this->assertNotContains('q_5jahre', $open);
        $this->assertNotContains('q_referenz', $open);
        $this->assertNotContains('q_hat_senator', $open);
        $this->assertContains('q_kreisprojekt', $open);
        $this->assertContains('q_ak_vorstand', $open);
    }

    public function test_no_supra_regional_work_keeps_alternative_gold_path_open(): void
    {
        $open = $this->openCodes($this->flow(), ['q_ak_vorstand' => 'yes', 'q_ueberregional' => 'no']);

        $this->assertContains('q_7jahre', $open);
        $this->assertNotContains('q_2jahre', $open);
    }

    public function test_state_question_is_only_asked_in_saxony_anhalt(): void
    {
        $this->assertContains('q_lsa', $this->openCodes($this->flow('ST'), []));
        $this->assertNotContains('q_lsa', $this->openCodes($this->flow('BY'), []));
        $this->assertNotContains('q_hat_lsa', $this->openCodes($this->flow('BY'), []));
    }

    public function test_under_two_years_implies_not_five_years(): void
    {
        $flow = $this->flow();

        $this->assertSame('no', $flow->effectiveAnswers(['q_2jahre' => 'no'])['q_5jahre']);
        $this->assertNotContains('q_5jahre', $this->openCodes($flow, ['q_2jahre' => 'no']));
        $this->assertArrayNotHasKey('q_5jahre', $flow->effectiveAnswers(['q_2jahre' => 'yes']));
    }

    public function test_explicit_answer_wins_over_implication(): void
    {
        $this->assertSame('yes', $this->flow()->effectiveAnswers(['q_2jahre' => 'no', 'q_5jahre' => 'yes'])['q_5jahre']);
    }

    public function test_external_supporters_skip_senator_questions(): void
    {
        $open = $this->openCodes($this->flow('ST', RecipientGroup::External), []);

        $this->assertNotContains('q_3vor40', $open);
        $this->assertNotContains('q_referenz', $open);
    }

    public function test_answering_everything_closes_the_flow(): void
    {
        $flow = $this->flow();
        $answers = [];

        for ($i = 0; $i < 30 && ($q = $flow->next($answers)); $i++) {
            $answers[$q->code] = 'yes';
        }

        $this->assertNull($flow->next($answers));
        $this->assertLessThan(count(CatalogFixture::questions()) + 1, $i);
    }

    public function test_honors_using_question(): void
    {
        $this->assertSame(['wjd-goldene-juniorennadel', 'jci-senatorenwuerde'], $this->flow()->honorsUsing('q_ueberregional'));
    }
}
