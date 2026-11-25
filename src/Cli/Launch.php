<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Cli;

use RuntimeException;
use Throwable;

final class Launch
{
    /**
     * @param list<string> $arguments
     */
    public static function run(array $arguments): int
    {
        try {
            $binary = Binary::forPackage()->ensure();

            if (function_exists('pcntl_exec')) {
                pcntl_exec($binary, $arguments);

                throw new RuntimeException(sprintf(
                    'cannot execute %s: %s',
                    $binary,
                    pcntl_strerror(pcntl_get_last_error()),
                ));
            }

            $pipes = [];
            $process = proc_open([$binary, ...$arguments], [STDIN, STDOUT, STDERR], $pipes);

            if ($process === false) {
                throw new RuntimeException(sprintf('cannot start %s', $binary));
            }

            return proc_close($process);
        } catch (Throwable $cause) {
            fwrite(STDERR, sprintf('opcov: %s%s', $cause->getMessage(), PHP_EOL));

            return 2;
        }
    }
}
