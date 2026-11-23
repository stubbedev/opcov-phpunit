<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Branch;

use OpCov\PHPUnit\Json;

final readonly class Outcome
{
    /**
     * @param int<0, max> $number
     */
    private function __construct(
        public int $number,
        public OutcomeKind $kind,
        public Branch $branch,
    ) {}

    /**
     * @param array<array-key, mixed> $outcome
     */
    public static function fromArray(array $outcome, string $what, int $testCount): self
    {
        return new self(
            Id::Outcome->number(Json::string($outcome['id'] ?? null, $what . '.id'), $what . '.id'),
            OutcomeKind::parse($outcome['kind'] ?? null, $what . '.kind'),
            new Branch(
                Json::nonNegativeInt($outcome['site'] ?? null, $what . '.site'),
                Json::positiveInt($outcome['line'] ?? null, $what . '.line'),
                Json::hits($outcome['hits'] ?? null, $what . '.hits', $testCount),
            ),
        );
    }
}
