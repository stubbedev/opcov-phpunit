<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Cli;

use RuntimeException;

final class Download
{
    private const int CONNECT_TIMEOUT_SECONDS = 30;
    private const int STALL_SECONDS = 60;
    private const string USER_AGENT = 'opcov-phpunit';

    public static function toFile(string $url, string $file): void
    {
        if (function_exists('curl_init')) {
            self::withCurl($url, $file);

            return;
        }

        self::withStreams($url, $file);
    }

    public static function toString(string $url, string $scratch): string
    {
        self::toFile($url, $scratch);

        try {
            $contents = file_get_contents($scratch);

            if ($contents === false) {
                throw new RuntimeException(sprintf('cannot read %s', $scratch));
            }

            return $contents;
        } finally {
            unlink($scratch);
        }
    }

    private static function withCurl(string $url, string $file): void
    {
        $output = fopen($file, mode: 'wb');

        if ($output === false) {
            throw new RuntimeException(sprintf('cannot write %s', $file));
        }

        try {
            $handle = curl_init($url);
            curl_setopt_array($handle, [
                CURLOPT_FILE => $output,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_FAILONERROR => true,
                CURLOPT_USERAGENT => self::USER_AGENT,
                CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT_SECONDS,
                CURLOPT_LOW_SPEED_LIMIT => 1,
                CURLOPT_LOW_SPEED_TIME => self::STALL_SECONDS,
            ]);

            if (curl_exec($handle) === false) {
                throw new RuntimeException(sprintf('cannot download %s: %s', $url, curl_error($handle)));
            }
        } finally {
            fclose($output);
        }
    }

    private static function withStreams(string $url, string $file): void
    {
        $context = stream_context_create(['http' => [
            'follow_location' => 1,
            'user_agent' => self::USER_AGENT,
            'timeout' => self::STALL_SECONDS,
        ]]);
        $warning = null;
        set_error_handler(static function (int $_level, string $message) use (&$warning): bool {
            $warning = $message;

            return true;
        });

        try {
            $copied = copy($url, $file, $context);
        } finally {
            restore_error_handler();
        }

        if (!$copied) {
            throw new RuntimeException(sprintf('cannot download %s: %s', $url, $warning ?? 'unknown error'));
        }
    }
}
