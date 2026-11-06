<?php

namespace Tests\Unit\Ehrungen;

use App\Domain\Ehrungen\Assessor;
use App\Domain\Ehrungen\HonorRule;
use App\Domain\Ehrungen\RecipientGroup;
use App\Domain\Ehrungen\ResultState;
use App\Domain\Ehrungen\RuleBasis;
use App\Domain\Ehrungen\Truth;
use PHPUnit\Framework\TestCase;
use Tests\Support\CatalogFixture;

class AssessorTest extends TestCase
{
    private function rule(string $code): HonorRule
    {
        foreach (CatalogFixture::rules() as $rule) {
            if ($rule->honorCode === $code) {
                return $rule;
            }
        }

        $this->fail("Ehrung {$code} fehlt im Katalog.");
    }

    public function test_unconfirmed_rule_needs_clarification_outside_pilot_mode(): void
    {
        $result = (new Assessor)->assess($this->rule('wjd-silberne-juniorennadel'), ['q_kreisprojekt' => 'yes', 'q_hat_silber' => 'no'], RecipientGroup::Member, false);

        $this->assertSame(ResultState::Clarification, $result->state);
        $this->assertSame(Assessor::REASON_PENDING_CONFIRMATION, $result->reason);
        $this->assertSame(Truth::True, $result->raw);
    }

    public function test_confirmed_rule_is_suitable(): void
    {
        $rule = $this->rule('wjd-silberne-juniorennadel');
        $confirmed = new HonorRule($rule->honorCode, $rule->criteria, RuleBasis::Confirmed);

        $result = (new Assessor)->assess($confirmed, ['q_training' => 'yes', 'q_hat_silber' => 'no'], RecipientGroup::Member, false);

        $this->assertSame(ResultState::Suitable, $result->state);
    }

    public function test_unknown_answer_never_turns_into_no(): void
    {
        $result = (new Assessor)->assess($this->rule('wjd-silberne-juniorennadel'), ['q_kreisprojekt' => 'unknown', 'q_training' => 'no', 'q_hat_silber' => 'no'], RecipientGroup::Member, true);

        $this->assertSame(ResultState::Possible, $result->state);
    }

    public function test_conflicting_gold_rule_always_needs_clarification_but_keeps_raw_result(): void
    {
        $result = (new Assessor)->assess($this->rule('wjd-goldene-juniorennadel'), ['q_ak_vorstand' => 'yes', 'q_ueberregional' => 'yes', 'q_2jahre' => 'yes', 'q_hat_gold' => 'no'], RecipientGroup::Member, true);

        $this->assertSame(ResultState::Clarification, $result->state);
        $this->assertSame(Assessor::REASON_CONFLICTING, $result->reason);
        $this->assertSame(Truth::True, $result->raw);
    }

    public function test_alternative_gold_path_is_blocked_until_confirmed(): void
    {
        $rule = $this->rule('wjd-goldene-juniorennadel');
        $confirmed = new HonorRule($rule->honorCode, $rule->criteria, RuleBasis::Confirmed);

        $result = (new Assessor)->assess($confirmed, ['q_ak_vorstand' => 'yes', 'q_ueberregional' => 'no', 'q_2jahre' => 'yes', 'q_7jahre' => 'yes', 'q_hat_gold' => 'no'], RecipientGroup::Member, true);

        $this->assertSame(ResultState::Clarification, $result->state);
        $this->assertSame(Assessor::REASON_BLOCKED_PATH, $result->reason);
    }

    public function test_incomplete_state_pin_is_only_excluded_when_purpose_is_clearly_not_met(): void
    {
        $assessor = new Assessor;
        $rule = $this->rule('wj-st-goldene-ehrennadel');

        $this->assertSame(ResultState::NotSuitable, $assessor->assess($rule, ['q_lsa' => 'no'], RecipientGroup::Member, true)->state);
        $this->assertSame(ResultState::Clarification, $assessor->assess($rule, ['q_lsa' => 'yes', 'q_hat_lsa' => 'no'], RecipientGroup::Member, true)->state);
    }

    public function test_existing_silver_pin_does_not_affect_other_honours(): void
    {
        $answers = ['q_hat_silber' => 'yes', 'q_5jahre' => 'yes', 'q_3vor40' => 'yes', 'q_funktion' => 'yes', 'q_verdienste' => 'yes', 'q_ueberregional' => 'yes', 'q_international' => 'yes', 'q_botschafter' => 'yes', 'q_referenz' => 'yes', 'q_hat_senator' => 'no'];

        $result = (new Assessor)->assess($this->rule('jci-senatorenwuerde'), $answers, RecipientGroup::Member, true);

        $this->assertSame(ResultState::Suitable, $result->state);
    }

    public function test_former_member_leads_to_clarification_not_exclusion(): void
    {
        $result = (new Assessor)->assess($this->rule('wjd-silberne-juniorennadel'), ['q_kreisprojekt' => 'yes', 'q_hat_silber' => 'no'], RecipientGroup::Former, true);

        $this->assertSame(ResultState::Clarification, $result->state);
        $this->assertSame(Assessor::REASON_RECIPIENT_UNCLEAR, $result->reason);
    }

    public function test_info_only_honour_is_not_evaluated(): void
    {
        $this->assertSame(ResultState::Info, (new Assessor)->assess($this->rule('jci-toyp'), [], RecipientGroup::Member, true)->state);
    }

    public function test_missing_published_rule_needs_clarification(): void
    {
        $rule = new HonorRule('x', null, RuleBasis::Confirmed, hasPublishedRule: false);

        $result = (new Assessor)->assess($rule, [], RecipientGroup::Member, true);

        $this->assertSame(ResultState::Clarification, $result->state);
        $this->assertSame(Assessor::REASON_NO_PUBLISHED_RULE, $result->reason);
    }

    public function test_changing_an_answer_recalculates_result(): void
    {
        $assessor = new Assessor;
        $rule = $this->rule('wjd-silberne-juniorennadel');
        $answers = ['q_kreisprojekt' => 'yes', 'q_hat_silber' => 'no'];

        $this->assertSame(ResultState::Suitable, $assessor->assess($rule, $answers, RecipientGroup::Member, true)->state);

        $answers['q_kreisprojekt'] = 'no';
        $answers['q_training'] = 'no';

        $this->assertSame(ResultState::NotSuitable, $assessor->assess($rule, $answers, RecipientGroup::Member, true)->state);
    }
}
