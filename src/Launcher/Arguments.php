<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Launcher;

use InvalidArgumentException;

final readonly class Arguments
{
    public const string USAGE = 'usage: opcov-phpunit [-d <setting>=<value>]... <runner script> [runner arguments]...';

    /**
     * @param list<IniSetting> $phpSettings
     * @param list<string> $runnerArguments
     */
    private function __construct(
        public array $phpSettings,
        public string $runner,
        public array $runnerArguments,
    ) {}

    /**
     * @param list<string> $arguments
     */
    public static function parse(array $arguments): self
    {
        $settings = [];

        while (true) {
            $argument = array_shift($arguments);

            if ($argument === null) {
                throw new InvalidArgumentException('the runner script is missing. ' . self::USAGE);
            }

            if (!str_starts_with($argument, '-d')) {
                return new self($settings, $argument, $arguments);
            }

            $setting = substr($argument, offset: 2);

            if ($setting === '') {
                $setting = array_shift(
                    $arguments,
                ) ?? throw new InvalidArgumentException('-d needs a <setting>=<value> argument. ' . self::USAGE);
            }

            $settings[] = IniSetting::parse($setting);
        }
    }
}
