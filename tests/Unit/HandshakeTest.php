<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Tests\Unit;

use InvalidArgumentException;
use OpCov\PHPUnit\Handshake;
use OpCov\PHPUnit\Version;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class HandshakeTest extends TestCase
{
    public function testPackageVersionIsTheWorkspaceVersion(): void
    {
        $manifest = file_get_contents(dirname(__DIR__, levels: 4) . '/Cargo.toml');
        self::assertIsString($manifest);
        self::assertMatchesRegularExpression(
            sprintf('/\[workspace\.package\][^\[]*\nversion = "%s"\n/', preg_quote(
                Handshake::PACKAGE_VERSION,
                delimiter: '/',
            )),
            $manifest,
        );
    }

    public function testAcceptsTheSameCompatibilityLine(): void
    {
        self::assertSame(Handshake::PACKAGE_VERSION, Handshake::check(Handshake::PACKAGE_VERSION)->toString());
    }

    public function testRefusesAMissingExtension(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('the opcov extension is not loaded');

        Handshake::check(null);
    }

    public function testRefusesAnotherCompatibilityLine(): void
    {
        $package = Version::parse(Handshake::PACKAGE_VERSION);
        $other = sprintf('%d.%d.0', $package->major + 1, $package->minor);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains(sprintf(
            'the opcov extension %s is not compatible with opcov/phpunit',
            $other,
        ));

        Handshake::check($other);
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function compatibility(): iterable
    {
        yield 'same 0.x minor' => ['0.1.0', '0.1.7', true];
        yield 'different 0.x minor' => ['0.1.0', '0.2.0', false];
        yield 'same major' => ['1.2.0', '1.9.3', true];
        yield 'different major' => ['1.2.0', '2.0.0', false];
        yield 'pre-release of the same line' => ['1.0.0', '1.1.0-rc.1', true];
    }

    #[DataProvider('compatibility')]
    public function testCompatibilityFollowsSemanticVersioning(string $left, string $right, bool $compatible): void
    {
        self::assertSame($compatible, Version::parse($left)->isCompatibleWith(Version::parse($right)));
    }

    public function testRejectsAMalformedVersion(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('"1.2" is not a semantic version');

        Version::parse('1.2');
    }
}
