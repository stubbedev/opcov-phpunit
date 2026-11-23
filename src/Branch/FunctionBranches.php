<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Branch;

use InvalidArgumentException;
use SebastianBergmann\CodeCoverage\Driver\Driver;

/**
 * @phpstan-type BranchType array{
 *     op_start: int,
 *     op_end: int,
 *     line_start: int,
 *     line_end: int,
 *     hit: int,
 *     out: array<int, int>,
 *     out_hit: array<int, int>,
 * }
 * @phpstan-type FunctionCoverageType array{branches: array<int, BranchType>, paths: array<int, never>}
 */
final readonly class FunctionBranches
{
    /**
     * @param non-empty-string $name
     * @param array<int<0, max>, Outcome> $branches
     * @param array<int<0, max>, true> $outcomes
     */
    private function __construct(
        public string $name,
        public array $branches,
        private array $outcomes,
    ) {}

    /**
     * @param non-empty-string $name
     * @param list<Outcome> $outcomes
     */
    public static function fromOutcomes(string $name, array $outcomes): self
    {
        $branches = [];
        $numbers = [];

        foreach ($outcomes as $outcome) {
            $numbers[$outcome->number] = true;

            if ($outcome->kind->countsTowardVerdict()) {
                $branches[$outcome->number] = $outcome;
            }
        }

        return new self($name, $branches, $numbers);
    }

    /**
     * @param array<int<0, max>, true> $taken
     *
     * @return FunctionCoverageType
     */
    public function coverage(array $taken): array
    {
        foreach (array_keys($taken) as $number) {
            if (!array_key_exists($number, $this->outcomes)) {
                throw new InvalidArgumentException(sprintf('%s has no outcome o%d', $this->name, $number));
            }
        }

        $branches = [];

        foreach ($this->branches as $number => $outcome) {
            $branches[$number] = [
                'op_start' => $outcome->site,
                'op_end' => $outcome->site,
                'line_start' => $outcome->line,
                'line_end' => $outcome->line,
                'hit' => array_key_exists($number, $taken) ? Driver::BRANCH_HIT : Driver::BRANCH_NOT_HIT,
                'out' => [],
                'out_hit' => [],
            ];
        }

        return ['branches' => $branches, 'paths' => []];
    }
}
