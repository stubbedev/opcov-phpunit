<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Cli;

use RuntimeException;

final readonly class Platform
{
    public const array SUPPORTED = ['x86_64-linux-musl', 'arm64-linux-musl', 'arm64-darwin-bsdlibc'];

    /**
     * @param non-empty-string $name
     */
    private function __construct(
        public string $name,
    ) {}

    public static function host(): self
    {
        return self::of(PHP_OS_FAMILY, php_uname('m'));
    }

    public static function of(string $family, string $machine): self
    {
        $architecture = match (strtolower($machine)) {
            'x86_64', 'amd64' => 'x86_64',
            'aarch64', 'arm64' => 'arm64',
            default => null,
        };
        $system = match ($family) {
            'Linux' => 'linux-musl',
            'Darwin' => 'darwin-bsdlibc',
            default => null,
        };
        $name = $architecture === null || $system === null ? null : $architecture . '-' . $system;

        if ($name === null || !in_array($name, self::SUPPORTED, strict: true)) {
            throw new RuntimeException(sprintf(
                'no prebuilt opcov CLI for %s on %s; prebuilt binaries exist for %s. Build it from source with '
                . '`cargo install --locked --git https://github.com/stubbedev/opcov opcov-cli`',
                $family,
                $machine,
                implode(', ', self::SUPPORTED),
            ));
        }

        return new self($name);
    }
}
