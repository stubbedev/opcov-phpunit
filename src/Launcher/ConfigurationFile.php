<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Launcher;

use InvalidArgumentException;

final class ConfigurationFile
{
    private const array DEFAULT_NAMES = ['phpunit.xml', 'phpunit.dist.xml', 'phpunit.xml.dist'];

    /**
     * @param list<string> $runnerArguments
     */
    public static function find(array $runnerArguments, string $workingDirectory): string
    {
        $explicit = self::explicit($runnerArguments);

        if ($explicit === null) {
            return self::inDirectory($workingDirectory);
        }

        $path = str_starts_with($explicit, '/') ? $explicit : $workingDirectory . '/' . $explicit;

        if (is_dir($path)) {
            return self::inDirectory($path);
        }

        if (!is_file($path)) {
            throw new InvalidArgumentException(sprintf('the configuration file %s does not exist', $path));
        }

        return $path;
    }

    /**
     * @param list<string> $arguments
     */
    private static function explicit(array $arguments): ?string
    {
        $explicit = null;

        while (($argument = array_shift($arguments)) !== null && $argument !== '--') {
            if ($argument === '--no-configuration') {
                throw new InvalidArgumentException(
                    '--no-configuration leaves no <source> to measure; set the opcov INI settings yourself instead',
                );
            }

            $explicit = match (true) {
                $argument === '-c', $argument === '--configuration' => array_shift(
                    $arguments,
                ) ?? throw new InvalidArgumentException(sprintf('%s needs a file or directory', $argument)),
                str_starts_with($argument, '--configuration=') => substr($argument, offset: 16),
                str_starts_with($argument, '-c') && !str_starts_with($argument, '-c-') => substr($argument, offset: 2),
                default => $explicit,
            };
        }

        return $explicit;
    }

    private static function inDirectory(string $directory): string
    {
        foreach (self::DEFAULT_NAMES as $name) {
            $candidate = $directory . '/' . $name;

            if (is_file($candidate)) {
                return $candidate;
            }
        }

        throw new InvalidArgumentException(sprintf(
            'no %s in %s; pass the configuration with -c',
            implode(', ', self::DEFAULT_NAMES),
            $directory,
        ));
    }
}
