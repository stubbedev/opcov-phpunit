<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Launcher;

use RuntimeException;
use Throwable;

final class Command
{
    /**
     * @param list<string> $arguments
     * @param array<string, string> $environment
     */
    public static function run(array $arguments, array $environment): never
    {
        try {
            foreach (['pcntl' => 'pcntl_exec', 'posix' => 'posix_geteuid'] as $extension => $function) {
                if (!function_exists($function)) {
                    throw new RuntimeException(sprintf(
                        'ext-%s is not available; enable it, or set the opcov INI settings yourself (docs/phpunit.md)',
                        $extension,
                    ));
                }
            }

            $workingDirectory = getcwd();

            if ($workingDirectory === false) {
                throw new RuntimeException('cannot determine the working directory');
            }

            $parsed = Arguments::parse($arguments);
            $configuration = realpath(ConfigurationFile::find($parsed->runnerArguments, $workingDirectory));

            if ($configuration === false) {
                throw new RuntimeException('cannot resolve the PHPUnit configuration file');
            }

            $identity = implode("\n", [
                $configuration,
                ...array_map(static fn(IniSetting $setting): string => $setting->toLine(), $parsed->phpSettings),
            ]);
            $directory = RunDirectory::forRun(sys_get_temp_dir(), posix_geteuid(), $identity);
            $directory->writeSettings(SourcePaths::fromConfiguration($configuration), $parsed->phpSettings);
            $environment['PHP_INI_SCAN_DIR'] = self::scanDirectories(
                $environment['PHP_INI_SCAN_DIR'] ?? null,
                $directory->path,
            );
            pcntl_exec(PHP_BINARY, [$parsed->runner, ...$parsed->runnerArguments], $environment);

            throw new RuntimeException(sprintf(
                'cannot execute %s: %s',
                PHP_BINARY,
                pcntl_strerror(pcntl_get_last_error()),
            ));
        } catch (Throwable $cause) {
            fwrite(STDERR, sprintf('opcov-phpunit: %s%s', $cause->getMessage(), PHP_EOL));

            exit(2);
        }
    }

    public static function scanDirectories(?string $inherited, string $directory): string
    {
        if ($inherited === null) {
            return ':' . $directory;
        }

        return $inherited . ':' . $directory;
    }
}
