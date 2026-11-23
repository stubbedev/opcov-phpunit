<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Branch;

use Closure;
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
            $function = Json::object($functions[$key] ?? null, $what);
            $parsed[$key] = FunctionBranches::of(
                FunctionName::of($key, $keyFile, $file),
                self::each(
                    $function['outcomes'] ?? null,
                    $what . '.outcomes',
                    /** @param array<array-key, mixed> $outcome */
                    static fn(array $outcome, string $at): Outcome => Outcome::fromArray($outcome, $at, $testCount),
                ),
                self::each(
                    $function['loops'] ?? null,
                    $what . '.loops',
                    /** @param array<array-key, mixed> $loop */
                    static fn(array $loop, string $at): Loop => Loop::fromArray($loop, $at, $testCount),
                ),
            );
        }

        return new self($file, $parsed);
    }

    /**
     * @param array<string, array{outcomes: array<string, true>, loops: array<string, array<string, true>>}> $functions
     *     the functions of a file as \opcov\stop() reports them
     *
     * @return array<string, array<int<0, max>, true>> taken branch and outcome numbers by function key
     */
    public function taken(array $functions): array
    {
        $taken = [];

        foreach ($functions as $key => $function) {
            $taken[$key] = $this->function($key)->taken($function['outcomes'], $function['loops']);
        }

        return $taken;
    }

    /**
     * @return array<int<0, max>, array<string, array<int<0, max>, true>>> branch numbers by test and function key
     */
    public function takenByTest(): array
    {
        $byTest = [];

        foreach ($this->functions as $key => $function) {
            foreach ($function->branches as $number => $branch) {
                foreach ($branch->hits as $test) {
                    $byTest[$test][$key][$number] = true;
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
     * @param array<string, array<int<0, max>, true>> $taken taken branch and outcome numbers by function key
     *
     * @return array<non-empty-string, FunctionCoverageType>
     */
    public function coverage(array $taken): array
    {
        $coverage = [];

        foreach ($taken as $key => $numbers) {
            $function = $this->function($key);

            if ($function->branches !== []) {
                $coverage[$function->name] = $function->coverage($numbers);
            }
        }

        return $coverage;
    }

    private function function(string $key): FunctionBranches
    {
        return (
            $this->functions[$key] ?? throw new InvalidArgumentException(sprintf(
                'the function %s is not in the model of %s',
                $key,
                $this->file,
            ))
        );
    }

    /**
     * @template T
     *
     * @param Closure(array<array-key, mixed>, string): T $parse
     *
     * @return list<T>
     */
    private static function each(mixed $entries, string $what, Closure $parse): array
    {
        $list = Json::list($entries, $what);

        return array_map(
            static fn(int $index, mixed $entry): mixed => $parse(
                Json::object($entry, sprintf('%s[%d]', $what, $index)),
                sprintf('%s[%d]', $what, $index),
            ),
            array_keys($list),
            $list,
        );
    }
}
