<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Cli;

use RuntimeException;

final class Cache
{
    public static function root(): string
    {
        $xdg = getenv('XDG_CACHE_HOME');

        if (is_string($xdg) && $xdg !== '') {
            return $xdg;
        }

        $home = getenv('HOME');

        if (!is_string($home) || $home === '') {
            throw new RuntimeException('cannot find a cache directory: neither XDG_CACHE_HOME nor HOME is set');
        }

        return $home . (PHP_OS_FAMILY === 'Darwin' ? '/Library/Caches' : '/.cache');
    }

    public static function directory(string $path): void
    {
        if (!is_dir($path) && !mkdir($path, permissions: 0o755, recursive: true) && !is_dir($path)) {
            throw new RuntimeException(sprintf('cannot create %s', $path));
        }
    }

    public static function executable(string $file): void
    {
        if (!chmod($file, permissions: 0o755)) {
            throw new RuntimeException(sprintf('cannot make %s executable', $file));
        }
    }

    public static function checksum(string $sums, string $asset): ?string
    {
        $matches = [];

        if (preg_match('/^([0-9a-f]{64}) [ *]' . preg_quote($asset, delimiter: '/') . '$/m', $sums, $matches) !== 1) {
            return null;
        }

        return $matches[1] ?? null;
    }
}
