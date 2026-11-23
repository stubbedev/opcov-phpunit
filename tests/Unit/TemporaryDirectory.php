<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Tests\Unit;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

final readonly class TemporaryDirectory
{
    private function __construct(
        public string $path,
    ) {}

    public static function create(): self
    {
        $path = sys_get_temp_dir() . '/opcov-phpunit-test-' . bin2hex(random_bytes(8));

        if (!mkdir($path, permissions: 0o700)) {
            throw new RuntimeException(sprintf('cannot create %s', $path));
        }

        $real = realpath($path);

        if ($real === false) {
            throw new RuntimeException(sprintf('cannot resolve %s', $path));
        }

        return new self($real);
    }

    public function write(string $relative, string $contents): string
    {
        $file = $this->path . '/' . $relative;
        $directory = dirname($file);

        if (!is_dir($directory) && !mkdir($directory, permissions: 0o700, recursive: true)) {
            throw new RuntimeException(sprintf('cannot create %s', $directory));
        }

        if (file_put_contents($file, $contents) !== strlen($contents)) {
            throw new RuntimeException(sprintf('cannot write %s', $file));
        }

        return $file;
    }

    public function remove(): void
    {
        $entries = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($entries as $entry) {
            if (!$entry instanceof SplFileInfo) {
                continue;
            }

            $removed = $entry->isDir() && !$entry->isLink()
                ? rmdir($entry->getPathname())
                : unlink($entry->getPathname());

            if (!$removed) {
                throw new RuntimeException(sprintf('cannot remove %s', $entry->getPathname()));
            }
        }

        rmdir($this->path);
    }
}
