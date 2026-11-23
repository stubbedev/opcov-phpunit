<?php

declare(strict_types=1);

namespace OpCov\PHPUnit;

use Closure;
use RuntimeException;
use Throwable;

final class AtomicFile
{
    /**
     * @param Closure(non-empty-string): void $write
     */
    public static function replace(string $target, Closure $write): void
    {
        $temporary = dirname($target) . '/.' . basename($target) . '.' . bin2hex(random_bytes(8)) . '.tmp';

        try {
            $write($temporary);

            if (!rename($temporary, $target)) {
                throw new RuntimeException(sprintf('cannot rename %s to %s', $temporary, $target));
            }
        } catch (Throwable $cause) {
            if (is_file($temporary)) {
                unlink($temporary);
            }

            throw $cause;
        }
    }

    public static function write(string $target, string $contents): void
    {
        self::replace($target, static function (string $temporary) use ($contents): void {
            if (file_put_contents($temporary, $contents) !== strlen($contents)) {
                throw new RuntimeException(sprintf('cannot write %s', $temporary));
            }
        });
    }
}
