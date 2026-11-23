<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Tests\Unit\Launcher;

use InvalidArgumentException;
use OpCov\PHPUnit\Launcher\Arguments;
use OpCov\PHPUnit\Launcher\Command;
use OpCov\PHPUnit\Launcher\IniSetting;
use PHPUnit\Framework\TestCase;

final class ArgumentsTest extends TestCase
{
    public function testLeadingPhpSettingsAreSeparatedFromTheRunner(): void
    {
        $arguments = Arguments::parse([
            '-dmemory_limit=-1',
            '-d',
            'error_reporting=-1',
            'vendor/bin/phpunit',
            '-d',
            'x',
        ]);

        self::assertSame(
            ["memory_limit='-1'\n", "error_reporting='-1'\n"],
            array_map(static fn(IniSetting $setting): string => $setting->toLine(), $arguments->phpSettings),
        );
        self::assertSame('vendor/bin/phpunit', $arguments->runner);
        self::assertSame(['-d', 'x'], $arguments->runnerArguments);
    }

    public function testOpcovSettingsAndUnquotableValuesAreRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains(
            'the launcher sets the opcov settings from the PHPUnit configuration',
        );

        IniSetting::parse('opcov.enabled=0');
    }

    public function testAQuoteInASettingIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('contains a single quote or a line break');

        Arguments::parse(["-dinclude_path=it's", 'vendor/bin/phpunit']);
    }

    public function testTheRunnerIsRequired(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('the runner script is missing');

        Arguments::parse(['-d', 'a=b']);
    }

    public function testTheScanDirectoryKeepsTheInheritedOnes(): void
    {
        self::assertSame(':/run', Command::scanDirectories(null, '/run'));
        self::assertSame('/etc/php.d:/run', Command::scanDirectories('/etc/php.d', '/run'));
    }
}
