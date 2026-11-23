<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Tests\Unit;

use InvalidArgumentException;
use OpCov\PHPUnit\OutputPath;
use OpCov\PHPUnit\Settings;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\Extension\ParameterCollection;
use RuntimeException;

final class SettingsTest extends TestCase
{
    public function testOutputIsRequired(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('the extension needs an "output" parameter');

        Settings::fromParameters(ParameterCollection::fromArray([]), __DIR__);
    }

    public function testRelativePathsResolveAgainstTheConfigurationDirectory(): void
    {
        $settings = Settings::fromParameters(ParameterCollection::fromArray([
            'output' => 'build/opcov.json',
            'root' => '..',
        ]), __DIR__);

        self::assertSame(__DIR__ . '/build/opcov.json', $settings->output->resolve(null));
        self::assertSame(['root' => dirname(__DIR__), 'absolute_paths' => false], $settings->reportOptions());
    }

    public function testAbsolutePathsHaveNoRoot(): void
    {
        $settings = Settings::fromParameters(ParameterCollection::fromArray([
            'output' => '/tmp/o.json',
            'absolute_paths' => 'true',
        ]), __DIR__);

        self::assertSame(['absolute_paths' => true], $settings->reportOptions());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('exclude each other');

        Settings::fromParameters(ParameterCollection::fromArray([
            'output' => 'o.json',
            'absolute_paths' => 'true',
            'root' => '.',
        ]), __DIR__);
    }

    public function testBooleansAreStrict(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains(
            'the "absolute_paths" parameter must be "true" or "false", got "yes"',
        );

        Settings::fromParameters(ParameterCollection::fromArray([
            'output' => 'o.json',
            'absolute_paths' => 'yes',
        ]), __DIR__);
    }

    public function testRootMustExist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('is not an existing directory');

        Settings::fromParameters(ParameterCollection::fromArray(['output' => 'o.json', 'root' => 'missing']), __DIR__);
    }

    public function testTheTestTokenNamesOneFilePerWorker(): void
    {
        $output = OutputPath::parse('/out/opcov.{TEST_TOKEN}.json', '/base');

        self::assertSame('/out/opcov.3.json', $output->resolve('3'));
    }

    public function testATokenWithoutAPlaceholderIsRefused(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('every worker would overwrite the same report');

        OutputPath::parse('opcov.json', '/base')->resolve('1');
    }

    public function testAPlaceholderWithoutATokenIsRefused(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('the TEST_TOKEN environment variable is not set');

        OutputPath::parse('opcov.{TEST_TOKEN}.json', '/base')->resolve(null);
    }
}
