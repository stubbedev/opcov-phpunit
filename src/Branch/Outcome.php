<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Branch;

use InvalidArgumentException;
use OpCov\PHPUnit\Json;

final readonly class Outcome
{
    /**
     * @param int<0, max> $number
     * @param int<0, max> $site
     * @param positive-int $line
     * @param list<int<0, max>> $hits
     */
    private function __construct(
        public int $number,
        public int $site,
        public OutcomeKind $kind,
        public int $line,
        public array $hits,
    ) {}

    /**
     * @param array<array-key, mixed> $outcome
     */
    public static function fromArray(array $outcome, string $what, int $testCount): self
    {
        $hits = Json::list($outcome['hits'] ?? null, $what . '.hits');

        return new self(
            self::number(Json::string($outcome['id'] ?? null, $what . '.id'), $what . '.id'),
            Json::nonNegativeInt($outcome['site'] ?? null, $what . '.site'),
            OutcomeKind::parse($outcome['kind'] ?? null, $what . '.kind'),
            Json::positiveInt($outcome['line'] ?? null, $what . '.line'),
            array_map(static fn(mixed $index): int => Json::index($index, $what . '.hits', $testCount), $hits),
        );
    }

    /**
     * @return int<0, max>
     */
    public static function number(string $id, string $what): int
    {
        if (preg_match('/\Ao(0|[1-9]\d{0,8})\z/', $id) !== 1) {
            throw new InvalidArgumentException(sprintf('%s "%s" is not an outcome id', $what, $id));
        }

        return Json::nonNegativeInt((int) substr($id, offset: 1), $what);
    }
}
