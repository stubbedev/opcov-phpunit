<?php

declare(strict_types=1);

namespace OpCov\PHPUnit;

use RuntimeException;

final class ReportOutput
{
    public static function verifySchema(string $path): void
    {
        $expected = sprintf('{"schema":"%s"', Handshake::REPORT_SCHEMA);
        $handle = fopen($path, mode: 'rb');

        if ($handle === false) {
            throw new RuntimeException(sprintf('cannot open the report %s to check its schema', $path));
        }

        try {
            $head = fread($handle, strlen($expected) + 1);
        } finally {
            fclose($handle);
        }

        if (
            $head === false
            || !str_starts_with($head, $expected)
            || !in_array($head[strlen($expected)], [',', '}'], strict: true)
        ) {
            throw new RuntimeException(sprintf(
                'the report %s does not start with schema %s; the extension writes a schema this package does not support',
                $path,
                Handshake::REPORT_SCHEMA,
            ));
        }
    }

    public static function ensureDirectory(string $path): void
    {
        $directory = dirname($path);

        if (is_dir($directory) || mkdir($directory, permissions: 0o777, recursive: true) || is_dir($directory)) {
            return;
        }

        throw new RuntimeException(sprintf('cannot create the report directory %s', $directory));
    }
}
