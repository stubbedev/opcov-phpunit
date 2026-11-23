<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Branch;

use InvalidArgumentException;

enum LoopFinding: string
{
    case Zero = 'zero';
    case Second = 'second';

    public static function parse(string $finding, string $what): self
    {
        return (
            self::tryFrom($finding) ?? throw new InvalidArgumentException(sprintf(
                '%s is the unknown loop finding "%s"',
                $what,
                $finding,
            ))
        );
    }

    /**
     * @param int<0, max> $first the branch number of the zero finding of loop l0
     * @param int<0, max> $loop
     *
     * @return int<0, max>
     */
    public function branch(int $first, int $loop): int
    {
        return $first
        + (2 * $loop)
        + match ($this) {
            self::Zero => 0,
            self::Second => 1,
        };
    }
}
