<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Branch;

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
 */
final readonly class Branch
{
    /**
     * @param int<0, max> $site
     * @param positive-int $line
     * @param list<int<0, max>> $hits
     */
    public function __construct(
        private int $site,
        private int $line,
        public array $hits,
    ) {}

    /**
     * @return BranchType
     */
    public function coverage(int $hit): array
    {
        return [
            'op_start' => $this->site,
            'op_end' => $this->site,
            'line_start' => $this->line,
            'line_end' => $this->line,
            'hit' => $hit,
            'out' => [],
            'out_hit' => [],
        ];
    }
}
