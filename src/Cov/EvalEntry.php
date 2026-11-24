<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Cov;

final class EvalEntry
{
    private const string NAME = '/\A.+\([0-9]+\) : (?:(?!\) : ).)+\z/s';

    public static function is(string $path): bool
    {
        return preg_match(self::NAME, $path) === 1;
    }
}
