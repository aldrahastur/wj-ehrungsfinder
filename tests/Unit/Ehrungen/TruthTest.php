<?php

use App\Domain\Ehrungen\Truth;

test('and is excluded by one definitely false criterion', function () {
    expect(Truth::all([Truth::True, Truth::Unknown, Truth::Blocked, Truth::False]))->toBe(Truth::False);
});

test('and with unknown keeps recommendation open', function () {
    expect(Truth::all([Truth::True, Truth::Unknown]))->toBe(Truth::Unknown)
        ->and(Truth::all([Truth::True, Truth::Unknown, Truth::Blocked]))->toBe(Truth::Blocked)
        ->and(Truth::all([Truth::True, Truth::True]))->toBe(Truth::True);
});

test('or is satisfied by one definitely met path', function () {
    expect(Truth::any([Truth::False, Truth::Blocked, Truth::True]))->toBe(Truth::True)
        ->and(Truth::any([Truth::False, Truth::Blocked]))->toBe(Truth::Blocked)
        ->and(Truth::any([Truth::False, Truth::Unknown]))->toBe(Truth::Unknown)
        ->and(Truth::any([Truth::False, Truth::False]))->toBe(Truth::False);
});

test('not keeps unknown and blocked', function () {
    expect(Truth::True->negate())->toBe(Truth::False)
        ->and(Truth::Unknown->negate())->toBe(Truth::Unknown)
        ->and(Truth::Blocked->negate())->toBe(Truth::Blocked);
});
