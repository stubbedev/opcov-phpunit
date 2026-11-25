<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Tests\Unit\Cli;

use OpCov\PHPUnit\Cli\Platform;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PlatformTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function supported(): iterable
    {
        yield 'linux x86_64' => ['Linux', 'x86_64', 'x86_64-linux-musl'];
        yield 'linux amd64' => ['Linux', 'amd64', 'x86_64-linux-musl'];
        yield 'linux aarch64' => ['Linux', 'aarch64', 'arm64-linux-musl'];
        yield 'macos arm64' => ['Darwin', 'arm64', 'arm64-darwin-bsdlibc'];
    }

    #[DataProvider('supported')]
    public function testNamesTheBinaryOfTheHost(string $family, string $machine, string $name): void
    {
        self::assertSame($name, Platform::of($family, $machine)->name);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function unsupported(): iterable
    {
        yield 'intel macos' => ['Darwin', 'x86_64'];
        yield 'windows' => ['Windows', 'AMD64'];
        yield '32-bit linux' => ['Linux', 'i686'];
    }

    #[DataProvider('unsupported')]
    public function testRefusesAHostWithoutABinary(string $family, string $machine): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains(sprintf(
            'no prebuilt opcov CLI for %s on %s; prebuilt binaries exist for x86_64-linux-musl, arm64-linux-musl, arm64-darwin-bsdlibc',
            $family,
            $machine,
        ));

        Platform::of($family, $machine);
    }
}
