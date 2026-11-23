<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Cov;

use Closure;
use RuntimeException;
use Throwable;

final class Command
{
    public const string USAGE = 'usage: opcov-cov <report.json> <out.cov>';

    /**
     * @param list<string> $arguments
     * @param Closure(string): void $printError
     */
    public static function run(array $arguments, Closure $printError): int
    {
        $input = array_shift($arguments);
        $output = array_shift($arguments);

        if ($input === null || $output === null || $arguments !== []) {
            $printError(self::USAGE);

            return 2;
        }

        try {
            $json = file_get_contents($input);

            if ($json === false) {
                throw new RuntimeException(sprintf('cannot read %s', $input));
            }

            CovFile::write(Converter::convert(Report::fromJson($json)), $output);
        } catch (Throwable $cause) {
            $printError(sprintf('opcov-cov: converting %s to %s failed: %s', $input, $output, $cause->getMessage()));

            return 2;
        }

        return 0;
    }
}
