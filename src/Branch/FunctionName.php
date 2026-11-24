<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Branch;

use InvalidArgumentException;
use OpCov\PHPUnit\Encoding;

final class FunctionName
{
    private const string MAIN = '{main}@';

    private const string CLOSURE = '{closure}@';

    private const string ANONYMOUS_CLASS = 'class@anonymous@';

    private const string DECLARED_LINES = '(?<lines>[1-9]\d*-[1-9]\d*)(?<ordinal>#[1-9]\d*)?';

    private const string MEMBER = '(?<member>\$[^:]+::(?:get|set)|[^:$]+)';

    /**
     * @param string $key the function key in opcov's canonical byte encoding
     * @param string $keyFile the bytes of the file as the function key names it: the report's files key, or the absolute path
     * @param non-empty-string $file the bytes of the absolute path of that file
     *
     * @return non-empty-string
     */
    public static function of(string $key, string $keyFile, string $file): string
    {
        $key = Encoding::decode($key, 'function key');

        if ($key === '') {
            throw new InvalidArgumentException(sprintf('a function of %s has an empty key', $file));
        }

        if ($key === self::MAIN . $keyFile) {
            return '{main}';
        }

        $declared = '/\A' . self::DECLARED_LINES . '%s\z/';

        if (str_starts_with($key, self::CLOSURE . $keyFile . ':')) {
            $parts = self::match(sprintf($declared, ''), substr($key, strlen(self::CLOSURE . $keyFile . ':')), $key);

            return sprintf('{closure:%s:%s}%s', $file, $parts['lines'] ?? '', $parts['ordinal'] ?? '');
        }

        if (str_starts_with($key, self::ANONYMOUS_CLASS . $keyFile . ':')) {
            $parts = self::match(
                sprintf($declared, '::' . self::MEMBER),
                substr($key, strlen(self::ANONYMOUS_CLASS . $keyFile . ':')),
                $key,
            );

            return sprintf(
                '%s%s:%s%s->%s',
                self::ANONYMOUS_CLASS,
                $file,
                $parts['lines'] ?? '',
                $parts['ordinal'] ?? '',
                $parts['member'] ?? '',
            );
        }

        $parts = self::match('/\A(?<owner>[^:{}@]+)(?:::' . self::MEMBER . ')?\z/', $key, $key);

        $member = $parts['member'] ?? null;

        return $member === null ? $key : sprintf('%s->%s', $parts['owner'] ?? '', $member);
    }

    /**
     * @param non-empty-string $pattern
     *
     * @return array<array-key, string>
     */
    private static function match(string $pattern, string $subject, string $key): array
    {
        $parts = [];

        if (preg_match($pattern, $subject, $parts, PREG_UNMATCHED_AS_NULL) !== 1) {
            throw new InvalidArgumentException(sprintf('"%s" is not an opcov function key of this file', $key));
        }

        return array_filter($parts, static fn(?string $part): bool => $part !== null);
    }
}
