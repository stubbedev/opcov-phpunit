<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Cov;

use OpCov\PHPUnit\Driver;
use RuntimeException;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Driver\Driver as CodeCoverageDriver;
use SebastianBergmann\CodeCoverage\Filter;

final class Converter
{
    private const string EXECUTABLE_LINES = 'opcov executable lines';

    public static function convert(Report $report): CodeCoverage
    {
        $filter = new Filter();
        $filter->includeFiles(array_keys($report->files));
        $coverage = new CodeCoverage(new Driver($report->recordedBy), $filter);
        $executable = [];
        $byTest = [];

        foreach ($report->files as $path => $file) {
            self::verifySource($path, $file->sha256);

            foreach ($file->lines as $line => $hits) {
                $executable[$path][$line] = CodeCoverageDriver::LINE_NOT_EXECUTED;

                foreach ($hits as $test) {
                    $byTest[$test][$path][$line] = CodeCoverageDriver::LINE_EXECUTED;
                }
            }
        }

        $coverage->append(RawLines::from($executable), self::EXECUTABLE_LINES, false);
        ksort($byTest);

        foreach ($byTest as $test => $lines) {
            $coverage->append(
                RawLines::from($lines),
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
