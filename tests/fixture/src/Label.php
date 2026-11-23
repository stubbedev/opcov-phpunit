<?php

declare(strict_types=1);

namespace Fixture;

use InvalidArgumentException;

final class Label
{
    public static function format(?string $name, string $city): string
    {
        $name ??= 'Customer';

        if ($city === '') {
            throw new InvalidArgumentException('a label needs a city');
        }

        return strtoupper($name) . ', ' . $city;
    }

    public static function prepare(): string
    {
        return 'prepared';
    }
}
