<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Cov;

use OpCov\PHPUnit\Driver;
use OpCov\PHPUnit\RawCoverage;
use RuntimeException;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Filter;

final class Converter
{
    private const string EXECUTABLE = 'opcov executable lines and outcomes';

    public static function convert(Report $report): CodeCoverage
    {
        $filter = new Filter();
        $filter->includeFiles(array_keys($report->files));
        $coverage = new CodeCoverage(new Driver($report->recordedBy), $filter);
        $executable = new RawCoverage(branches: true);
        $linesByTest = [];
        $takenByTest = [];

        foreach ($report->files as $path => $file) {
            self::verifySource($path, $file->sha256);
            $executable->unexecuted($path, array_keys($file->lines), $file->branches);

            foreach ($file->linesByTest() as $test => $lines) {
                $linesByTest[$test][$path] = $lines;
            }

            foreach ($file->branches->takenByTest() as $test => $taken) {
                $takenByTest[$test][$path] = $taken;
            }
        }

        $coverage->append($executable->build(), self::EXECUTABLE, false);
        $tests = array_keys($linesByTest + $takenByTest);
        sort($tests);

        foreach ($tests as $test) {
            $executed = new RawCoverage(branches: true);

            $lines = $linesByTest[$test] ?? [];
            $taken = $takenByTest[$test] ?? [];

            foreach (array_keys($lines + $taken) as $path) {
                $executed->executed(
                    $path,
                    $lines[$path] ?? [],
                    (
                        $report->files[$path] ?? throw new RuntimeException(sprintf('the report has no file %s', $path))
                    )->branches,
                    $taken[$path] ?? [],
                );
            }

            $coverage->append(
                $executed->build(),
                $report->tests[$test] ?? throw new RuntimeException(sprintf(
                    'test index %d is outside the test table',
                    $test,
                )),
            );
        }

        return $coverage;
    }

    private static function verifySource(string $path, string $sha256): void
    {
        $actual = hash_file('sha256', $path);

        if ($actual === false) {
            throw new RuntimeException(sprintf('cannot read the measured source %s', $path));
        }

        if ($actual !== $sha256) {
            throw new RuntimeException(sprintf(
                'the measured source %s changed since the report was written (SHA-256 %s, report %s)',
                $path,
                $actual,
                $sha256,
            ));
        }
    }
}
