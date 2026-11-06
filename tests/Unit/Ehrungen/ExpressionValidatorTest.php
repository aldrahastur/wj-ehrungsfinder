<?php

use App\Domain\Ehrungen\ExpressionValidator;
use Tests\Support\CatalogFixture;

test('catalog criteria are valid', function () {
    $validator = new ExpressionValidator;

    foreach (CatalogFixture::data()['honors'] as $honor) {
        if ($honor['rule']['criteria'] !== null) {
            expect($validator->validate($honor['rule']['criteria'], CatalogFixture::questionCodes()))->toBe([], $honor['code']);
        }
    }
});

test('reports unknown question and bad structure', function () {
    $validator = new ExpressionValidator;
    $known = ['q_a'];

    expect($validator->validate(['question' => 'q_x'], $known))->not->toBe([])
        ->and($validator->validate(['all' => []], $known))->not->toBe([])
        ->and($validator->validate(['all' => [['question' => 'q_a']], 'any' => []], $known))->not->toBe([])
        ->and($validator->validate(['recipient' => ['member' => 'T']], $known))->not->toBe([])
        ->and($validator->validate(['prior' => 'q_a', 'mode' => 'maybe'], $known))->not->toBe([])
        ->and($validator->validate(['blocked' => ['en' => 'x']], $known))->not->toBe([])
        ->and($validator->validate(['not' => ['question' => 'q_a']], $known))->toBe([]);
});
