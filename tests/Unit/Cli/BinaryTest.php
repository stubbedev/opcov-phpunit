<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Tests\Unit\Cli;

use OpCov\PHPUnit\Cli\Binary;
use OpCov\PHPUnit\Cli\Platform;
use OpCov\PHPUnit\Tests\Unit\TemporaryDirectory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class BinaryTest extends TestCase
{
    private const string VERSION = '9.8.7';
    private const string ASSET = 'opcov-cli-v9.8.7-x86_64-linux-musl';
    private const string PROGRAM = "#!/bin/sh\necho opcov\n";

    private TemporaryDirectory $directory;

    #[\Override]
    protected function setUp(): void
    {
        $this->directory = TemporaryDirectory::create();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->directory->remove();
    }

    private function release(string $program, string $listed): void
    {
        $this->directory->write('release/' . self::ASSET, $program);
        $this->directory->write(
            'release/SHA256SUMS',
            hash('sha256', data: 'other')
            . "  opcov-cli-v9.8.7-arm64-linux-musl\n"
            . hash('sha256', $listed)
            . '  '
            . self::ASSET
            . "\n",
        );
    }

    private function binary(string $target): Binary
    {
        return new Binary(
            'file://' . $this->directory->path . '/release',
            self::VERSION,
            Platform::of('Linux', 'x86_64'),
            $this->directory->path . '/cache',
            $this->directory->path . '/' . $target,
        );
    }

    public function testDownloadsVerifiesCachesAndPlacesTheBinary(): void
    {
        $this->release(self::PROGRAM, self::PROGRAM);

        $placed = $this->binary('vendor/bin/opcov-x86_64-linux-musl')->ensure();

        self::assertSame($this->directory->path . '/vendor/bin/opcov-x86_64-linux-musl', $placed);
        self::assertSame(self::PROGRAM, file_get_contents($placed));
        self::assertTrue(is_executable($placed), 'the placed binary is executable');
        self::assertSame(self::PROGRAM, file_get_contents($this->directory->path . '/cache/' . self::ASSET));
        self::assertSame(
            [self::ASSET],
            array_values(array_diff((array) scandir($this->directory->path . '/cache'), ['.', '..'])),
        );
    }

    public function testReusesThePlacedAndTheCachedBinary(): void
    {
        $this->release(self::PROGRAM, self::PROGRAM);
        $this->binary('first')->ensure();
        unlink($this->directory->path . '/release/' . self::ASSET);
        unlink($this->directory->path . '/release/SHA256SUMS');

        self::assertSame(self::PROGRAM, file_get_contents($this->binary('first')->ensure()));
        self::assertSame(self::PROGRAM, file_get_contents($this->binary('second')->ensure()));
    }

    public function testRefusesABinaryWhoseChecksumDiffers(): void
    {
        $this->release('tampered', self::PROGRAM);

        try {
            $this->binary('target')->ensure();
            self::fail('a tampered binary was accepted');
        } catch (RuntimeException $failure) {
            self::assertStringContainsString(
                sprintf(
                    '%s has sha256 %s, but SHA256SUMS lists %s',
                    self::ASSET,
                    hash('sha256', data: 'tampered'),
                    hash('sha256', self::PROGRAM),
                ),
                $failure->getMessage(),
            );
        }

        self::assertFileDoesNotExist($this->directory->path . '/target');
        self::assertFileDoesNotExist($this->directory->path . '/cache/' . self::ASSET);
    }

    public function testRefusesAReleaseThatDoesNotListTheAsset(): void
    {
        $this->directory->write('release/SHA256SUMS', hash('sha256', data: 'other') . "  something-else\n");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('/release/SHA256SUMS does not list ' . self::ASSET);

        $this->binary('target')->ensure();
    }

    public function testRefusesAMissingRelease(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains(
            'cannot download file://' . $this->directory->path . '/release/SHA256SUMS',
        );

        $this->binary('target')->ensure();
    }
}
