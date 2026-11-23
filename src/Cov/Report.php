<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Cov;

use InvalidArgumentException;
use JsonException;
use OpCov\PHPUnit\Handshake;
use OpCov\PHPUnit\Json;
use OpCov\PHPUnit\Version;

final readonly class Report
{
    /**
     * @param list<non-empty-string> $tests
     * @param array<non-empty-string, ReportFile> $files
     */
    private function __construct(
        public Version $recordedBy,
        public array $tests,
        public array $files,
    ) {}

    public static function fromJson(string $json): self
    {
        try {
            $document = Json::object(json_decode($json, associative: true, flags: JSON_THROW_ON_ERROR), 'the report');
        } catch (JsonException $cause) {
            throw new InvalidArgumentException('the report is not valid JSON: ' . $cause->getMessage(), 0, $cause);
        }

        if (($document['schema'] ?? null) !== Handshake::REPORT_SCHEMA) {
            throw new InvalidArgumentException(sprintf(
                'the report has schema %s; this converter reads %s',
                json_encode($document['schema'] ?? null, flags: JSON_UNESCAPED_SLASHES),
                Handshake::REPORT_SCHEMA,
            ));
        }

        $generator = Json::object($document['generator'] ?? null, 'generator');
        $tests = self::tests($document['tests'] ?? null);

        return new self(
            Version::parse(Json::string($generator['opcov'] ?? null, 'generator.opcov')),
            $tests,
            ReportFile::all(
                Json::object($document['files'] ?? null, 'files'),
                self::root(Json::object($document['paths'] ?? null, 'paths')),
                count($tests),
            ),
        );
    }

    /**
     * @param array<array-key, mixed> $paths
     */
    private static function root(array $paths): ?string
    {
        return match ($paths['mode'] ?? null) {
            'relative' => Json::string($paths['root'] ?? null, 'paths.root'),
            'absolute' => null,
            default => throw new InvalidArgumentException('paths.mode is neither "relative" nor "absolute"'),
        };
    }

    /**
     * @return list<non-empty-string>
     */
    private static function tests(mixed $value): array
    {
        return array_map(
            static fn(int $index, mixed $test): string => Json::nonEmptyString($test, sprintf('tests[%d]', $index)),
            array_keys($list = Json::list($value, 'tests')),
            $list,
        );
    }
}
