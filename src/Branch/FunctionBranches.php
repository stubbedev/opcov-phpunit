<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Branch;

use InvalidArgumentException;
use SebastianBergmann\CodeCoverage\Driver\Driver;

/**
 * @phpstan-import-type BranchType from Branch
 * @phpstan-type FunctionCoverageType array{branches: array<int, BranchType>, paths: array<int, never>}
 */
final readonly class FunctionBranches
{
    /**
     * @param non-empty-string $name
     * @param array<int<0, max>, Branch> $branches
     * @param array<int<0, max>, true> $outcomes
     * @param int<0, max> $firstLoopBranch
     */
    private function __construct(
        public string $name,
        public array $branches,
        private array $outcomes,
        private int $firstLoopBranch,
    ) {}

    /**
     * @param non-empty-string $name
     * @param list<Outcome> $outcomes
     * @param list<Loop> $loops
     */
    public static function of(string $name, array $outcomes, array $loops): self
    {
        $branches = [];
        $numbers = [];

        foreach ($outcomes as $outcome) {
            $numbers[$outcome->number] = true;

            if ($outcome->kind->countsTowardVerdict()) {
                $branches[$outcome->number] = $outcome->branch;
            }
        }

        $firstLoopBranch = $numbers === [] ? 0 : max(array_keys($numbers)) + 1;

        $loopBranches = array_map(
            /** @return array<int<0, max>, Branch> */
            static fn(Loop $loop): array => $loop->branches($firstLoopBranch),
            $loops,
        );

        return new self($name, array_replace($branches, ...$loopBranches), $numbers, $firstLoopBranch);
    }

    /**
     * @param array<string, true> $outcomes taken outcome ids
     * @param array<string, array<string, true>> $loops findings by loop id, as \opcov\stop() reports them
     *
     * @return array<int<0, max>, true> taken branch and outcome numbers
     */
    public function taken(array $outcomes, array $loops): array
    {
        $taken = [];

        foreach (array_keys($outcomes) as $id) {
            $number = Id::Outcome->number($id, $this->name);

            if (!array_key_exists($number, $this->outcomes)) {
                throw new InvalidArgumentException(sprintf('%s has no outcome %s', $this->name, $id));
            }

            $taken[$number] = true;
        }

        foreach ($loops as $id => $findings) {
            $loop = Id::Loop->number($id, $this->name);
            $what = sprintf('%s loop %s', $this->name, $id);

            foreach (array_keys($findings) as $name) {
                $number = LoopFinding::parse($name, $what)->branch($this->firstLoopBranch, $loop);

                if (!array_key_exists($number, $this->branches)) {
                    throw new InvalidArgumentException(sprintf('%s has no finding "%s"', $what, $name));
                }

                $taken[$number] = true;
            }
        }

        return $taken;
    }

    /**
     * @param array<int<0, max>, true> $taken
     *
     * @return FunctionCoverageType
     */
    public function coverage(array $taken): array
    {
        $numbers = array_keys($this->branches);
        $branches = array_map(
            /** @return BranchType */
            static fn(int $number, Branch $branch): array => $branch->coverage(
                array_key_exists($number, $taken) ? Driver::BRANCH_HIT : Driver::BRANCH_NOT_HIT,
            ),
            $numbers,
            $this->branches,
        );

        return ['branches' => array_combine($numbers, $branches), 'paths' => []];
    }
}
