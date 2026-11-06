<?php

namespace Tests\Unit\Ehrungen;

use App\Domain\Ehrungen\Assessor;
use App\Domain\Ehrungen\QuestionFlow;
use App\Domain\Ehrungen\RecipientGroup;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\CatalogFixture;

class ReferenceCasesTest extends TestCase
{
    /**
     * @return iterable<string, array{0: array<string, mixed>}>
     */
    public static function cases(): iterable
    {
        foreach (CatalogFixture::data()['reference_cases'] as $case) {
            yield $case['name'] => [$case];
        }
    }

    /**
     * @param  array<string, mixed>  $case
     */
    #[DataProvider('cases')]
    public function test_reference_case_gives_expected_result(array $case): void
    {
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

        $this->assertSame($case['expected'], $actual);
    }
}
