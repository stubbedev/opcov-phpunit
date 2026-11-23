<?php

declare(strict_types=1);

namespace Fixture;

final class Shipping
{
    public static function cost(int $grams, int $orderTotal): int
    {
        if ($orderTotal >= 10_000) {
            return 0;
        }

        $parcels = 0;

        foreach ([1_000, 5_000] as $limit) {
            if ($grams > $limit) {
                $parcels++;
            }
        }

        return match ($parcels) {
            0 => 490,
            1 => 790,
            default => 1_290,
        };
    }

    public static function express(int $grams): int
    {
        return self::cost($grams, 0) * 2;
    }
}
