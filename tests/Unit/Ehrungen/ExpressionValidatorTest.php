<?php

namespace Tests\Unit\Ehrungen;

use App\Domain\Ehrungen\ExpressionValidator;
use PHPUnit\Framework\TestCase;
use Tests\Support\CatalogFixture;

class ExpressionValidatorTest extends TestCase
{
    public function test_catalog_criteria_are_valid(): void
    {
        $validator = new ExpressionValidator;

        foreach (CatalogFixture::data()['honors'] as $honor) {
            if ($honor['rule']['criteria'] !== null) {
                $this->assertSame([], $validator->validate($honor['rule']['criteria'], CatalogFixture::questionCodes()), $honor['code']);
            }
        }
    }

    public function test_reports_unknown_question_and_bad_structure(): void
    {
        $validator = new ExpressionValidator;
        $known = ['q_a'];

        $this->assertNotSame([], $validator->validate(['question' => 'q_x'], $known));
        $this->assertNotSame([], $validator->validate(['all' => []], $known));
        $this->assertNotSame([], $validator->validate(['all' => [['question' => 'q_a']], 'any' => []], $known));
        $this->assertNotSame([], $validator->validate(['recipient' => ['member' => 'T']], $known));
        $this->assertNotSame([], $validator->validate(['prior' => 'q_a', 'mode' => 'maybe'], $known));
        $this->assertNotSame([], $validator->validate(['blocked' => ['en' => 'x']], $known));
        $this->assertSame([], $validator->validate(['not' => ['question' => 'q_a']], $known));
    }
}
