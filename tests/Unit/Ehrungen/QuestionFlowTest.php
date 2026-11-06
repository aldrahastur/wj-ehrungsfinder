<?php

use App\Domain\Ehrungen\QuestionFlow;
use App\Domain\Ehrungen\RecipientGroup;
use Tests\Support\CatalogFixture;

function questionFlow(string $region = 'ST', RecipientGroup $group = RecipientGroup::Member): QuestionFlow
{
    return new QuestionFlow(CatalogFixture::questions(), CatalogFixture::rules(), $region, $group);
}

/**
 * @param  array<string, string>  $answers
 * @return list<string>
 */
function openCodes(QuestionFlow $flow, array $answers): array
{
    return array_map(fn ($q) => $q->code, $flow->open($answers));
}

test('starts with first question', function () {
    expect(questionFlow()->next([])?->code)->toBe('q_kreisprojekt');
});

test('no international engagement ends only the senator path', function () {
    expect(openCodes(questionFlow(), ['q_international' => 'no']))
        ->not->toContain('q_5jahre')
        ->not->toContain('q_referenz')
        ->not->toContain('q_hat_senator')
        ->toContain('q_kreisprojekt', 'q_ak_vorstand');
});

test('no supra regional work keeps alternative gold path open', function () {
    expect(openCodes(questionFlow(), ['q_ak_vorstand' => 'yes', 'q_ueberregional' => 'no']))
        ->toContain('q_7jahre')
        ->not->toContain('q_2jahre');
});

test('state question is only asked in saxony anhalt', function () {
    expect(openCodes(questionFlow('ST'), []))->toContain('q_lsa')
        ->and(openCodes(questionFlow('BY'), []))->not->toContain('q_lsa')
        ->not->toContain('q_hat_lsa');
});

test('under two years implies not five years', function () {
    $flow = questionFlow();

    expect($flow->effectiveAnswers(['q_2jahre' => 'no'])['q_5jahre'])->toBe('no')
        ->and(openCodes($flow, ['q_2jahre' => 'no']))->not->toContain('q_5jahre')
        ->and($flow->effectiveAnswers(['q_2jahre' => 'yes']))->not->toHaveKey('q_5jahre');
});

test('explicit answer wins over implication', function () {
    expect(questionFlow()->effectiveAnswers(['q_2jahre' => 'no', 'q_5jahre' => 'yes'])['q_5jahre'])->toBe('yes');
});

test('external supporters skip senator questions', function () {
    expect(openCodes(questionFlow('ST', RecipientGroup::External), []))
        ->not->toContain('q_3vor40')
        ->not->toContain('q_referenz');
});

test('answering everything closes the flow', function () {
    $flow = questionFlow();
    $answers = [];

    for ($i = 0; $i < 30 && ($q = $flow->next($answers)); $i++) {
        $answers[$q->code] = 'yes';
    }

    expect($flow->next($answers))->toBeNull()
        ->and($i)->toBeLessThan(count(CatalogFixture::questions()) + 1);
});

test('honors using question', function () {
    expect(questionFlow()->honorsUsing('q_ueberregional'))->toBe(['wjd-goldene-juniorennadel', 'jci-senatorenwuerde']);
});
