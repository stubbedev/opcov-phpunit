<?php

declare(strict_types=1);

namespace OpCov\PHPUnit;

use Throwable;

final class Abort
{
    public const int EXIT_CODE = 3;

    public static function because(string $attempt, Throwable $cause): never
    {
        fwrite(STDERR, sprintf('opcov: %s failed: %s (%s)%s', $attempt, $cause->getMessage(), $cause::class, PHP_EOL));

        exit(self::EXIT_CODE);
    }
}
