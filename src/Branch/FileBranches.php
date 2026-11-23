<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Branch;

use InvalidArgumentException;
use OpCov\PHPUnit\Json;

/**
 * @phpstan-import-type FunctionCoverageType from FunctionBranches
 */
final readonly class FileBranches
{
    /**
     * @param array<string, FunctionBranches> $functions
     */
    private function __construct(
        private string $file,
        public array $functions,
    ) {}

    /**
     * @param array<array-key, mixed> $functions
     * @param string $keyFile the file as the function keys name it
     * @param non-empty-string $file the absolute path of the file
     */
    public static function fromModel(array $functions, string $keyFile, string $file, int $testCount): self
    {
        $parsed = [];

        foreach (array_keys($functions) as $key) {
            $key = (string) $key;
            $what = sprintf('%s functions.%s', $file, $key);
            $outcomes = Json::list(
                Json::object($functions[$key] ?? null, $what)['outcomes'] ?? null,
                $what . '.outcomes',
            );
            $parsed[$key] = FunctionBranches::fromOutcomes(
                FunctionName::of($key, $keyFile, $file),
                array_map(
                    static fn(int $index, mixed $outcome): Outcome => Outcome::fromArray(
                        Json::object($outcome, sprintf('%s.outcomes[%d]', $what, $index)),
                        sprintf('%s.outcomes[%d]', $what, $index),
                        $testCount,
                    ),
                    array_keys($outcomes),
                    $outcomes,
                ),
            );
        }

        return new self($file, $parsed);
    }

    /**
     * @return array<int<0, max>, array<string, array<int<0, max>, true>>> outcome numbers by test and function key
     */
    public function takenByTest(): array
    {
        $byTest = [];

        foreach ($this->functions as $key => $function) {
            foreach ($function->branches as $outcome) {
                foreach ($outcome->hits as $test) {
                    $byTest[$test][$key][$outcome->number] = true;
                }
            }
        }

        return $byTest;
    }

    /**
     * @return array<non-empty-string, FunctionCoverageType>
     */
    public function unexecuted(): array
    {
        return $this->coverage(array_fill_keys(array_keys($this->functions), []));
    }

    /**
     * @param array<string, array<int<0, max>, true>> $taken outcome numbers by function key
     *
     * @return array<non-empty-string, FunctionCoverageType>
     */
    public function coverage(array $taken): array
    {
        $coverage = [];

        foreach ($taken as $key => $numbers) {
            $function = $this->functions[$key] ?? throw new InvalidArgumentException(sprintf(
                'the function %s is not in the model of %s',
                $key,
                $this->file,
            ));

            if ($function->branches !== []) {
                $coverage[$function->name] = $function->coverage($numbers);
            }
        }

        return $coverage;
    }
}
