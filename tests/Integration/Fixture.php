<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Tests\Integration;

use RuntimeException;

final readonly class Fixture
{
    public const string ROOT = __DIR__ . '/../fixture';

    /**
     * @param non-empty-string $runnerDirectory
     */
    private function __construct(
        public string $runnerDirectory,
        private string $extension,
    ) {}

    public static function runner(string $name): self
    {
        $extension = getenv('OPCOV_EXTENSION');

        if ($extension === false || !is_file($extension)) {
            throw new RuntimeException(
                'set OPCOV_EXTENSION to the opcov extension built from this repository (target/debug/libopcov_ext.so)',
            );
        }

        $directory = self::ROOT . '/' . $name;

        if (!is_file($directory . '/vendor/autoload.php')) {
            throw new RuntimeException(sprintf('run composer install in %s first', $directory));
        }

        return new self($directory, $extension);
    }

    /**
     * @param list<string> $arguments
     */
    public function php(array $arguments): Process
    {
        return Process::run([PHP_BINARY, ...$arguments], $this->runnerDirectory);
    }

    /**
     * @param list<string> $runner
     */
    public function launch(array $runner): Process
    {
        return $this->php(['vendor/bin/opcov-phpunit', '-d', 'extension=' . $this->extension, ...$runner]);
    }

    public function extensionSetting(): string
    {
        return 'extension=' . $this->extension;
    }

    public function clean(): void
    {
        $files = glob($this->runnerDirectory . '/build/*');

        if ($files === false) {
            throw new RuntimeException(sprintf('cannot list %s/build', $this->runnerDirectory));
        }

        foreach ($files as $file) {
            if (!unlink($file)) {
                throw new RuntimeException(sprintf('cannot remove %s', $file));
            }
        }
    }

    public function report(string $name): Report
    {
        return Report::read($this->runnerDirectory . '/build/' . $name);
    }
}
