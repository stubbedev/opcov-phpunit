<?php

declare(strict_types=1);

namespace OpCov\PHPUnit;

use OpCov\PHPUnit\Branch\FileBranches;
use OpCov\PHPUnit\Branch\FunctionBranches;
use SebastianBergmann\CodeCoverage\Data\RawCodeCoverageData;
use SebastianBergmann\CodeCoverage\Driver\Driver as CodeCoverageDriver;

/**
 * @phpstan-import-type FunctionCoverageType from FunctionBranches
 */
final class RawCoverage
{
    /**
     * @var array<non-empty-string, array<positive-int, int>>
     */
    private array $lines = [];

    /**
     * @var array<non-empty-string, array<non-empty-string, FunctionCoverageType>>
     */
    private array $functions = [];

    public function __construct(
        public readonly bool $branches,
    ) {}

    /**
     * @param non-empty-string $file
     * @param list<positive-int> $lines
     */
    public function unexecuted(string $file, array $lines, FileBranches $model): void
    {
        foreach ($lines as $line) {
            $this->lines[$file][$line] = CodeCoverageDriver::LINE_NOT_EXECUTED;
        }

        if ($this->branches) {
            $this->addFunctions($file, $model->unexecuted());
        }
    }

    /**
     * @param non-empty-string $file
     * @param list<positive-int> $lines
     * @param array<string, array<int<0, max>, true>> $taken outcome numbers by function key
     */
    public function executed(string $file, array $lines, FileBranches $model, array $taken): void
    {
        foreach ($lines as $line) {
            $this->lines[$file][$line] = CodeCoverageDriver::LINE_EXECUTED;
        }

        if ($this->branches) {
            $this->addFunctions($file, $model->coverage($taken));
        }
    }

    /**
     * @param non-empty-string $file
     * @param array<non-empty-string, FunctionCoverageType> $functions
     */
    private function addFunctions(string $file, array $functions): void
    {
        foreach ($functions as $name => $function) {
            $this->functions[$file][$name] = $function;
        }
    }

    public function build(): RawCodeCoverageData
    {
        if (method_exists(RawCodeCoverageData::class, 'fromLineAndBranchCoverage')) {
            return RawCodeCoverageData::fromLineAndBranchCoverage($this->lines, $this->functions);
        }

        $files = [];

        foreach (array_keys($this->lines + $this->functions) as $file) {
            $files[$file] = ['lines' => $this->lines[$file] ?? [], 'functions' => $this->functions[$file] ?? []];
        }

        return RawCodeCoverageData::fromXdebugWithPathCoverage($files);
    }
}
