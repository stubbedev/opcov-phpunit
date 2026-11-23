<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Tests\Unit\Launcher;

use OpCov\PHPUnit\Launcher\IniSetting;
use OpCov\PHPUnit\Launcher\RunDirectory;
use OpCov\PHPUnit\Launcher\SourcePaths;
use OpCov\PHPUnit\Tests\Unit\TemporaryDirectory;
use Override;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RunDirectoryTest extends TestCase
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

    public function testTheRunDirectoryHoldsTheIniAndListfiles(): void
    {
        $this->directory->write('src/A.php', '<?php');
        $configuration = $this->directory->write(
            'phpunit.xml',
            '<phpunit><source><include><directory>src</directory></include></source></phpunit>',
        );
        mkdir($this->directory->path . '/tmp');
        $run = RunDirectory::forRun($this->directory->path . '/tmp', posix_geteuid(), $configuration);
        $run->writeSettings(SourcePaths::fromConfiguration($configuration), [IniSetting::parse('memory_limit=1G')]);

        self::assertSame(0o700, fileperms($run->path) & 0o777);
        self::assertSame($this->directory->path . "/src\n", file_get_contents($run->path . '/collect_paths.txt'));
        self::assertSame(
            sprintf(
                "memory_limit='1G'\nopcov.enabled='1'\nopcov.collect_paths='@%s/collect_paths.txt'\nopcov.exclude_paths=''\n",
                $run->path,
            ),
            file_get_contents($run->path . '/opcov.ini'),
        );
        self::assertSame(
            [
                'memory_limit' => '1G',
                'opcov.enabled' => '1',
                'opcov.collect_paths' => '@' . $run->path . '/collect_paths.txt',
                'opcov.exclude_paths' => '',
            ],
            parse_ini_file($run->path . '/opcov.ini'),
        );
    }

    public function testARunDirectoryOthersCanReadIsRefused(): void
    {
        $shared = $this->directory->path . '/tmp/opcov-' . posix_geteuid();
        mkdir($shared, permissions: 0o755, recursive: true);
        chmod($shared, permissions: 0o755);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('accessible only to it (mode 0700)');

        RunDirectory::forRun($this->directory->path . '/tmp', posix_geteuid(), '/x/phpunit.xml');
    }
}
