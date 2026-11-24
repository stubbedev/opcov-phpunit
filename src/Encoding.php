<?php

declare(strict_types=1);

namespace OpCov\PHPUnit;

use InvalidArgumentException;
use RuntimeException;

final class Encoding
{
    private const string VALID_MULTIBYTE_CHARACTER =
        '[\xC2-\xDF][\x80-\xBF]'
            . '|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]'
            . '|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2}';

    public static function encode(string $bytes): string
    {
        return self::replaced(preg_replace_callback(
            '/(?:' . self::VALID_MULTIBYTE_CHARACTER . ')+|%|[\x80-\xFF]/',
            /** @param array<array-key, string> $match */
            static fn(array $match): string => self::escaped($match[0] ?? ''),
            $bytes,
        ));
    }

    public static function decode(string $text, string $what): string
    {
        $bytes = self::replaced(preg_replace_callback(
            '/%([0-9A-Fa-f]{2})/',
            /** @param array<array-key, string> $match */
            static fn(array $match): string => chr((int) hexdec($match[1] ?? '')),
            $text,
        ));

        if (self::encode($bytes) !== $text) {
            throw new InvalidArgumentException(sprintf(
                '%s "%s" is not in canonical byte encoding: every %% must start %%25 or the %%XX escape, in uppercase hex, of a byte that is not part of valid UTF-8',
                $what,
                $text,
            ));
        }

        return $bytes;
    }

    private static function escaped(string $matched): string
    {
        return match (true) {
            $matched === '%' => '%25',
            strlen($matched) === 1 && ord($matched) >= 0x80 => sprintf('%%%02X', ord($matched)),
            default => $matched,
        };
    }

    private static function replaced(?string $result): string
    {
        return $result ?? throw new RuntimeException('PCRE failed: ' . preg_last_error_msg());
    }
}
