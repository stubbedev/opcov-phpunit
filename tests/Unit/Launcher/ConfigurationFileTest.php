<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Tests\Unit\Launcher;

use InvalidArgumentException;
use OpCov\PHPUnit\Launcher\ConfigurationFile;
use OpCov\PHPUnit\Tests\Unit\TemporaryDirectory;
use Override;
use PHPUnit\Framework\TestCase;

final class ConfigurationFileTest extends TestCase
{
    private TemporaryDirectory $directory;

    #[Override]
    protected function setUp(): void
    {
        $this->directory = TemporaryDirectory::create();
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->directory->remove();
    }

    public function testTheConfigurationIsFoundLikePHPUnitFindsIt(): void
    {
        $dist = $this->directory->write('phpunit.xml.dist', '<phpunit/>');
        self::assertSame($dist, ConfigurationFile::find([], $this->directory->path));

        $xml = $this->directory->write('phpunit.xml', '<phpunit/>');
        self::assertSame($xml, ConfigurationFile::find(['--filter', 'x'], $this->directory->path));

        $other = $this->directory->write('ci/other.xml', '<phpunit/>');
        self::assertSame($other, ConfigurationFile::find(['-c', 'ci/other.xml'], $this->directory->path));
        self::assertSame($other, ConfigurationFile::find(['--configuration=ci/other.xml'], $this->directory->path));
        self::assertSame($other, ConfigurationFile::find(['-cci/other.xml'], $this->directory->path));
        self::assertSame($xml, ConfigurationFile::find(['--', '-c', 'ci/other.xml'], $this->directory->path));
    }

    public function testNoConfigurationIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('--no-configuration leaves no <source> to measure');

        ConfigurationFile::find(['--no-configuration'], $this->directory->path);
    }
}
