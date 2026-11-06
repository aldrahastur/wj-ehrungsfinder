<?php

use App\Domain\Ehrungen\Assessor;
use App\Domain\Ehrungen\HonorRule;
use App\Domain\Ehrungen\RecipientGroup;
use App\Domain\Ehrungen\ResultState;
use App\Domain\Ehrungen\RuleBasis;
use App\Domain\Ehrungen\Truth;
use Tests\Support\CatalogFixture;

function catalogRule(string $code): HonorRule
{
    foreach (CatalogFixture::rules() as $rule) {
        if ($rule->honorCode === $code) {
            return $rule;
        }
    }

    throw new RuntimeException("Ehrung {$code} fehlt im Katalog.");
}

test('unconfirmed rule needs clarification outside pilot mode', function () {
    $result = (new Assessor)->assess(catalogRule('wjd-silberne-juniorennadel'), ['q_kreisprojekt' => 'yes', 'q_hat_silber' => 'no'], RecipientGroup::Member, false);

    expect($result->state)->toBe(ResultState::Clarification)
        ->and($result->reason)->toBe(Assessor::REASON_PENDING_CONFIRMATION)
        ->and($result->raw)->toBe(Truth::True);
});

test('confirmed rule is suitable', function () {
    $rule = catalogRule('wjd-silberne-juniorennadel');
    $confirmed = new HonorRule($rule->honorCode, $rule->criteria, RuleBasis::Confirmed);

    $result = (new Assessor)->assess($confirmed, ['q_training' => 'yes', 'q_hat_silber' => 'no'], RecipientGroup::Member, false);

    expect($result->state)->toBe(ResultState::Suitable);
});

test('unknown answer never turns into no', function () {
    $result = (new Assessor)->assess(catalogRule('wjd-silberne-juniorennadel'), ['q_kreisprojekt' => 'unknown', 'q_training' => 'no', 'q_hat_silber' => 'no'], RecipientGroup::Member, true);

    expect($result->state)->toBe(ResultState::Possible);
});

test('conflicting gold rule always needs clarification but keeps raw result', function () {
    $result = (new Assessor)->assess(catalogRule('wjd-goldene-juniorennadel'), ['q_ak_vorstand' => 'yes', 'q_ueberregional' => 'yes', 'q_2jahre' => 'yes', 'q_hat_gold' => 'no'], RecipientGroup::Member, true);

    expect($result->state)->toBe(ResultState::Clarification)
        ->and($result->reason)->toBe(Assessor::REASON_CONFLICTING)
        ->and($result->raw)->toBe(Truth::True);
});

test('alternative gold path is blocked until confirmed', function () {
    $rule = catalogRule('wjd-goldene-juniorennadel');
    $confirmed = new HonorRule($rule->honorCode, $rule->criteria, RuleBasis::Confirmed);

    $result = (new Assessor)->assess($confirmed, ['q_ak_vorstand' => 'yes', 'q_ueberregional' => 'no', 'q_2jahre' => 'yes', 'q_7jahre' => 'yes', 'q_hat_gold' => 'no'], RecipientGroup::Member, true);

    expect($result->state)->toBe(ResultState::Clarification)
        ->and($result->reason)->toBe(Assessor::REASON_BLOCKED_PATH);
});

test('incomplete state pin is only excluded when purpose is clearly not met', function () {
    $assessor = new Assessor;
    $rule = catalogRule('wj-st-goldene-ehrennadel');

    expect($assessor->assess($rule, ['q_lsa' => 'no'], RecipientGroup::Member, true)->state)->toBe(ResultState::NotSuitable)
        ->and($assessor->assess($rule, ['q_lsa' => 'yes', 'q_hat_lsa' => 'no'], RecipientGroup::Member, true)->state)->toBe(ResultState::Clarification);
});

test('existing silver pin does not affect other honours', function () {
    $answers = ['q_hat_silber' => 'yes', 'q_5jahre' => 'yes', 'q_3vor40' => 'yes', 'q_funktion' => 'yes', 'q_verdienste' => 'yes', 'q_ueberregional' => 'yes', 'q_international' => 'yes', 'q_botschafter' => 'yes', 'q_referenz' => 'yes', 'q_hat_senator' => 'no'];

    $result = (new Assessor)->assess(catalogRule('jci-senatorenwuerde'), $answers, RecipientGroup::Member, true);

    expect($result->state)->toBe(ResultState::Suitable);
});

test('former member leads to clarification not exclusion', function () {
    $result = (new Assessor)->assess(catalogRule('wjd-silberne-juniorennadel'), ['q_kreisprojekt' => 'yes', 'q_hat_silber' => 'no'], RecipientGroup::Former, true);

    expect($result->state)->toBe(ResultState::Clarification)
        ->and($result->reason)->toBe(Assessor::REASON_RECIPIENT_UNCLEAR);
});

test('info only honour is not evaluated', function () {
    expect((new Assessor)->assess(catalogRule('jci-toyp'), [], RecipientGroup::Member, true)->state)->toBe(ResultState::Info);
});

test('missing published rule needs clarification', function () {
    $rule = new HonorRule('x', null, RuleBasis::Confirmed, hasPublishedRule: false);

    $result = (new Assessor)->assess($rule, [], RecipientGroup::Member, true);

    expect($result->state)->toBe(ResultState::Clarification)
        ->and($result->reason)->toBe(Assessor::REASON_NO_PUBLISHED_RULE);
});

test('changing an answer recalculates result', function () {
    $assessor = new Assessor;
    $rule = catalogRule('wjd-silberne-juniorennadel');
    $answers = ['q_kreisprojekt' => 'yes', 'q_hat_silber' => 'no'];

    expect($assessor->assess($rule, $answers, RecipientGroup::Member, true)->state)->toBe(ResultState::Suitable);

    $answers['q_kreisprojekt'] = 'no';
    $answers['q_training'] = 'no';

    expect($assessor->assess($rule, $answers, RecipientGroup::Member, true)->state)->toBe(ResultState::NotSuitable);
});
