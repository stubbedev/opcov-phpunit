<?php

declare(strict_types=1);

use Fixture\Shipping;

test('large orders ship for free', function (): void {
    expect(Shipping::cost(20_000, 10_000))->toBe(0);
});

test('cost depends on weight', function (int $grams, int $expected): void {
    expect(Shipping::cost($grams, 100))->toBe($expected);
})->with([
    'light' => [500, 490],
    'heavy' => [9_000, 1_290],
]);

test('express fails on purpose', function (): void {
    expect(Shipping::express(2_000))->toBe(1);
});

test('skipped after running code', function (): void {
    Shipping::express(100);
    $this->markTestSkipped('skipped on purpose; its hits are discarded');
});
