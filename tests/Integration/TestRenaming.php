<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Tests\Integration;

use OpCov\PHPUnit\Json;
use RuntimeException;

final readonly class TestRenaming
{
    /**
     * @param list<int> $map
     */
    public function __construct(
        private array $map,
    ) {}

    /**
     * @return array<array-key, mixed>
     */
    public function files(mixed $files): array
    {
        $files = Json::object($files, 'files');

        foreach (array_keys($files) as $path) {
            $file = Json::object($files[$path] ?? null, 'file');
            $file['lines'] = array_map($this->hits(...), Json::object($file['lines'] ?? null, 'lines'));
            $file['functions'] = array_map($this->function(...), Json::object($file['functions'] ?? null, 'functions'));
            $files[$path] = $file;
        }

        return $files;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function function(mixed $function): array
    {
        $function = Json::object($function, 'function');
        $function['outcomes'] = array_map($this->outcome(...), Json::list($function['outcomes'] ?? null, 'outcomes'));
        $function['loops'] = array_map($this->loop(...), Json::list($function['loops'] ?? null, 'loops'));

        return $function;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function outcome(mixed $outcome): array
    {
        $outcome = Json::object($outcome, 'outcome');
        $outcome['hits'] = $this->hits($outcome['hits'] ?? null);

        return $outcome;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function loop(mixed $loop): array
    {
        $loop = Json::object($loop, 'loop');
        $loop['zero'] = $this->hits($loop['zero'] ?? null);
        $loop['second'] = $this->hits($loop['second'] ?? null);

        return $loop;
    }

    /**
     * @return list<int>
     */
    private function hits(mixed $hits): array
    {
        $renamed = array_map(
            fn(mixed $index): int => (
                $this->map[Json::index($index, 'hit', count($this->map))] ?? throw new RuntimeException(
                    'a hit refers to no test',
                )
            ),
            Json::list($hits, 'hit list'),
        );
        sort($renamed);

        return $renamed;
    }
}
