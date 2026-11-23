<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Tests\Integration;

use Closure;
use OpCov\PHPUnit\Json;
use RuntimeException;

final readonly class Report
{
    /**
     * @param array<array-key, mixed> $document
     */
    private function __construct(
        public array $document,
    ) {}

    public static function read(string $path): self
    {
        $json = file_get_contents($path);

        if ($json === false) {
            throw new RuntimeException(sprintf('cannot read the report %s', $path));
        }

        return new self(Json::object(json_decode($json, associative: true, flags: JSON_THROW_ON_ERROR), $path));
    }

    /**
     * @return list<string>
     */
    public function tests(): array
    {
        return array_map(
            static fn(mixed $test): string => Json::string($test, 'test'),
            Json::list($this->document['tests'] ?? null, 'tests'),
        );
    }

    /**
     * @param Closure(string): string $rename
     */
    public function withTestNames(Closure $rename): self
    {
        $renamed = array_map($rename, $this->tests());
        $sorted = $renamed;
        sort($sorted, SORT_STRING);
        $position = array_flip($sorted);
        $map = array_map(
            static fn(string $name): int => $position[$name] ?? throw new RuntimeException('a renamed test was lost'),
            $renamed,
        );
        $document = $this->document;
        $document['tests'] = $sorted;
        $document['files'] = new TestRenaming($map)->files($document['files'] ?? null);

        return new self($document);
    }

    /**
     * @return array<string, array<int, list<string>>>
     */
    public function hitsByLine(): array
    {
        $tests = $this->tests();
        $hits = [];

        $files = Json::object($this->document['files'] ?? null, 'files');

        foreach (array_keys($files) as $path) {
            $lines = Json::object(Json::object($files[$path] ?? null, 'file')['lines'] ?? null, 'lines');

            foreach (array_keys($lines) as $line) {
                $hits[(string) $path][(int) $line] = array_map(
                    static fn(mixed $index): string => (
                        $tests[Json::index($index, 'hit', count($tests))] ?? throw new RuntimeException(
                            'a hit refers to no test',
                        )
                    ),
                    Json::list($lines[$line] ?? null, 'hit list'),
                );
            }
        }

        return $hits;
    }
}
