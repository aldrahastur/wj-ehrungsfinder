<?php

use App\Domain\Ehrungen\Assessor;
use App\Domain\Ehrungen\QuestionFlow;
use App\Domain\Ehrungen\RecipientGroup;
use Tests\Support\CatalogFixture;

dataset('reference cases', function () {
    foreach (CatalogFixture::data()['reference_cases'] as $case) {
        yield $case['name'] => [$case];
    }
});

/**
 * @param  array<string, mixed>  $case
 */
test('reference case gives expected result', function (array $case) {
    $group = RecipientGroup::from($case['group']);
    $flow = new QuestionFlow(CatalogFixture::questions(), CatalogFixture::rules(), $case['region'], $group);
    $answers = $flow->effectiveAnswers($case['answers']);
    $assessor = new Assessor;

    $actual = [];
    foreach (CatalogFixture::rules() as $rule) {
        if ($rule->appliesTo($case['region'])) {
            $actual[$rule->honorCode] = $assessor->assess($rule, $answers, $group, true)->state->value;
        }
    }

    expect($actual)->toBe($case['expected']);
})->with('reference cases');
