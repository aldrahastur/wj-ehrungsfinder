<?php

namespace Tests\Unit\Ehrungen;

use App\Domain\Ehrungen\Truth;
use PHPUnit\Framework\TestCase;

class TruthTest extends TestCase
{
    public function test_and_is_excluded_by_one_definitely_false_criterion(): void
    {
        $this->assertSame(Truth::False, Truth::all([Truth::True, Truth::Unknown, Truth::Blocked, Truth::False]));
    }

    public function test_and_with_unknown_keeps_recommendation_open(): void
    {
        $this->assertSame(Truth::Unknown, Truth::all([Truth::True, Truth::Unknown]));
        $this->assertSame(Truth::Blocked, Truth::all([Truth::True, Truth::Unknown, Truth::Blocked]));
        $this->assertSame(Truth::True, Truth::all([Truth::True, Truth::True]));
    }

    public function test_or_is_satisfied_by_one_definitely_met_path(): void
    {
        $this->assertSame(Truth::True, Truth::any([Truth::False, Truth::Blocked, Truth::True]));
        $this->assertSame(Truth::Blocked, Truth::any([Truth::False, Truth::Blocked]));
        $this->assertSame(Truth::Unknown, Truth::any([Truth::False, Truth::Unknown]));
        $this->assertSame(Truth::False, Truth::any([Truth::False, Truth::False]));
    }

    public function test_not_keeps_unknown_and_blocked(): void
    {
        $this->assertSame(Truth::False, Truth::True->negate());
        $this->assertSame(Truth::Unknown, Truth::Unknown->negate());
        $this->assertSame(Truth::Blocked, Truth::Blocked->negate());
    }
}
