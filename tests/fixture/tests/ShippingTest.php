<?php

declare(strict_types=1);

namespace Fixture\Tests;

use Fixture\Shipping;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ShippingTest extends TestCase
{
    public function testLargeOrdersShipForFree(): void
    {
        self::assertSame(0, Shipping::cost(20_000, 10_000));
    }

    /**
     * @return iterable<string, array{int, int}>
     */
    public static function weights(): iterable
    {
        yield 'light' => [500, 490];
        yield 'heavy' => [9_000, 1_290];
    }

    #[DataProvider('weights')]
    public function testCostDependsOnWeight(int $grams, int $expected): void
    {
        self::assertSame($expected, Shipping::cost($grams, 100));
    }

    public function testExpressFailsOnPurpose(): void
    {
        self::assertSame(1, Shipping::express(2_000));
    }

    public function testSkippedAfterRunningCode(): void
    {
        Shipping::express(100);
        self::markTestSkipped('skipped on purpose; its hits are discarded');
    }
}
