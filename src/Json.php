<?php

declare(strict_types=1);

namespace OpCov\PHPUnit;

use InvalidArgumentException;

final class Json
{
    /**
     * @return array<array-key, mixed>
     */
    public static function object(mixed $value, string $what): array
    {
        if (!is_array($value)) {
            throw new InvalidArgumentException(sprintf('%s is not an object', $what));
        }

        return $value;
    }

    /**
     * @return list<mixed>
     */
    public static function list(mixed $value, string $what): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            throw new InvalidArgumentException(sprintf('%s is not an array', $what));
        }

        return $value;
    }

    public static function string(mixed $value, string $what): string
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException(sprintf('%s is not a string', $what));
        }

        return $value;
    }

    /**
     * @return non-empty-string
     */
    public static function nonEmptyString(mixed $value, string $what): string
    {
        $string = self::string($value, $what);

        if ($string === '') {
            throw new InvalidArgumentException(sprintf('%s is empty', $what));
        }

        return $string;
    }

    /**
     * @return int<0, max>
     */
    public static function nonNegativeInt(mixed $value, string $what): int
    {
        if (!is_int($value) || $value < 0) {
            throw new InvalidArgumentException(sprintf('%s is not a non-negative integer', $what));
        }

        return $value;
    }

    /**
     * @return positive-int
     */
    public static function positiveInt(mixed $value, string $what): int
    {
        if (!is_int($value) || $value < 1) {
            throw new InvalidArgumentException(sprintf('%s is not a positive integer', $what));
        }

        return $value;
    }

    /**
     * @return int<0, max>
     */
    public static function index(mixed $value, string $what, int $count): int
    {
        if (!is_int($value) || $value < 0 || $value >= $count) {
            throw new InvalidArgumentException(sprintf('%s refers to a test index outside the test table', $what));
        }

        return $value;
    }

    /**
     * @return list<int<0, max>>
     */
    public static function hits(mixed $value, string $what, int $count): array
    {
        return array_map(static fn(mixed $index): int => self::index($index, $what, $count), self::list($value, $what));
    }
}
