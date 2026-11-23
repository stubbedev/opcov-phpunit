<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Cov;

use InvalidArgumentException;
use OpCov\PHPUnit\Branch\FileBranches;
use OpCov\PHPUnit\Json;

final readonly class ReportFile
{
    /**
     * @param array<positive-int, list<int<0, max>>> $lines
     */
    private function __construct(
        public string $sha256,
        public array $lines,
        public FileBranches $branches,
    ) {}

    /**
     * @param array<array-key, mixed> $entries
     *
     * @return array<non-empty-string, ReportFile>
     */
    public static function all(array $entries, ?string $root, int $testCount): array
    {
        $files = [];

        foreach (array_keys($entries) as $key) {
            $key = (string) $key;
            $path = $root === null ? $key : $root . '/' . $key;

            if ($path === '' || !str_starts_with($path, '/')) {
                throw new InvalidArgumentException(sprintf(
                    'files key "%s" does not resolve to an absolute path',
                    $key,
                ));
            }

            $files[$path] = self::fromArray(
                Json::object($entries[$key] ?? null, 'files.' . $key),
                $key,
                $path,
                $testCount,
            );
        }

        return $files;
    }

    /**
     * @param array<array-key, mixed> $file
     * @param non-empty-string $path
     */
    public static function fromArray(array $file, string $key, string $path, int $testCount): self
    {
        $sha256 = Json::string($file['sha256'] ?? null, sprintf('files.%s.sha256', $key));

        if (preg_match('/\A[0-9a-f]{64}\z/', $sha256) !== 1) {
            throw new InvalidArgumentException(sprintf('files.%s.sha256 is not 64 lowercase hex digits', $key));
        }

        $entries = Json::object($file['lines'] ?? null, sprintf('files.%s.lines', $key));
        $lines = [];

        foreach (array_keys($entries) as $line) {
            $number = (int) $line;

            if ($number < 1 || (string) $number !== (string) $line) {
                throw new InvalidArgumentException(sprintf(
                    'files.%s.lines has the invalid line number "%s"',
                    $key,
                    $line,
                ));
            }

            $what = sprintf('files.%s.lines.%d', $key, $number);
            $lines[$number] = Json::hits($entries[$line] ?? null, $what, $testCount);
        }

        return new self(
            $sha256,
            $lines,
            FileBranches::fromModel(
                Json::object($file['functions'] ?? null, sprintf('files.%s.functions', $key)),
                $key,
                $path,
                $testCount,
            ),
        );
    }

    /**
     * @return array<int<0, max>, list<positive-int>>
     */
    public function linesByTest(): array
    {
        $byTest = [];

        foreach ($this->lines as $line => $tests) {
            foreach ($tests as $test) {
                $byTest[$test][] = $line;
            }
        }

        return $byTest;
    }
}
