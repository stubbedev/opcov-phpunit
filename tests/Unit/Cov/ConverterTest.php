<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Tests\Unit\Cov;

use InvalidArgumentException;
use OpCov\PHPUnit\Cov\Command;
use OpCov\PHPUnit\Cov\Report;
use OpCov\PHPUnit\Tests\Unit\TemporaryDirectory;
use Override;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\Data\ProcessedCodeCoverageData;
use SebastianBergmann\CodeCoverage\Serialization\Unserializer;

final class ConverterTest extends TestCase
{
    private const string SOURCE = <<<'PHP'
        <?php

        function choose(bool $first): int
        {
            if ($first) {
                return 1;
            }

            return 2;
        }

        PHP;

    private TemporaryDirectory $directory;

    #[Override]
    protected function setUp(): void
    {
        $this->directory = TemporaryDirectory::create();
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->directory->remove();
    }

    public function testLinesOutcomesAndAttributionSurviveTheConversion(): void
    {
        $source = $this->directory->write('src/choose.php', self::SOURCE);
        $report = $this->directory->write('opcov.json', $this->report(hash_file('sha256', $source)));
        $cov = $this->directory->path . '/out/coverage.cov';
        mkdir(dirname($cov));

        self::assertSame(0, Command::run([$report, $cov], $this->failOnError(...)));

        $serialized = new Unserializer()->unserialize($cov);
        $data = $serialized['codeCoverage'];
        self::assertInstanceOf(ProcessedCodeCoverageData::class, $data);
        self::assertSame(['T::first', 'T::second'], array_keys($serialized['testResults']));
        $names = $data->testIds();
        $lines = [];

        foreach ($data->lineCoverage() as $file => $fileLines) {
            foreach ($fileLines as $line => $tests) {
                $lines[basename($file)][$line] = array_map(
                    static fn(int $index): string => $names[$index] ?? '?',
                    array_keys($tests ?? []),
                );
            }
        }

        self::assertSame(
            ['choose.php' => [5 => ['T::first', 'T::second'], 6 => ['T::first'], 9 => ['T::second']]],
            $lines,
        );
        $branches = [];

        foreach ($data->functionCoverage() as $functions) {
            foreach ($functions as $name => $function) {
                foreach ($function->branches as $id => $branch) {
                    $branches[$name][$id] = [
                        $branch->line_start,
                        array_map(static fn(int $index): string => $names[$index] ?? '?', array_keys($branch->hit)),
                    ];
                }
            }
        }

        self::assertSame(['choose' => [0 => [5, ['T::first']], 1 => [5, ['T::second']]]], $branches);
        self::assertSame(['out/coverage.cov'], $this->filesIn('out'));
    }

    public function testAChangedSourceIsRefused(): void
    {
        $this->directory->write('src/choose.php', self::SOURCE);
        $report = $this->directory->write('opcov.json', $this->report(str_repeat('0', times: 64)));
        $cov = $this->directory->path . '/coverage.cov';

        $errors = [];

        self::assertSame(2, Command::run([$report, $cov], static function (string $message) use (&$errors): void {
            $errors[] = $message;
        }));
        self::assertFileDoesNotExist($cov);
        self::assertCount(1, $errors);
        self::assertStringContainsString('changed since the report was written', $errors[0] ?? '');
    }

    public function testAnotherSchemaIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains(
            'the report has schema "opcov-report/2"; this converter reads opcov-report/1',
        );

        Report::fromJson('{"schema":"opcov-report/2"}');
    }

    public function testATestIndexOutsideTheTableIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('files./a.php.lines.3 refers to a test index outside the test table');

        Report::fromJson(
            '{"schema":"opcov-report/1","generator":{"opcov":"0.1.0"},"paths":{"mode":"absolute"},"tests":["T::a"],'
            . '"files":{"/a.php":{"sha256":"'
            . str_repeat('a', times: 64)
            . '","lines":{"3":[1]}}}}',
        );
    }

    public function testUsageIsPrintedForWrongArguments(): void
    {
        $errors = [];

        self::assertSame(2, Command::run(['only-one'], static function (string $message) use (&$errors): void {
            $errors[] = $message;
        }));
        self::assertSame([Command::USAGE], $errors);
    }

    private function failOnError(string $message): void
    {
        self::fail($message);
    }

    private function report(string|false $sha256): string
    {
        self::assertIsString($sha256);

        return json_encode([
            'schema' => 'opcov-report/1',
            'generator' => ['opcov' => '0.1.0', 'php' => PHP_VERSION, 'zts' => false, 'vm' => 'HYBRID'],
            'settings' => [
                'collect_paths' => ['src'],
                'exclude_paths' => [],
                'exclude_pattern' => null,
                'strict' => true,
            ],
            'paths' => ['mode' => 'relative', 'root' => $this->directory->path],
            'tests' => ['T::first', 'T::second'],
            'files' => [
                'src/choose.php' => [
                    'sha256' => $sha256,
                    'lines' => ['5' => [0, 1], '6' => [0], '9' => [1]],
                    'functions' => [
                        '{main}@src/choose.php' => ['span' => null, 'outcomes' => [], 'loops' => []],
                        'choose' => [
                            'span' => null,
                            'outcomes' => [
                                [
                                    'id' => 'o0',
                                    'site' => 1,
                                    'kind' => 'IfTrue',
                                    'line' => 5,
                                    'span' => null,
                                    'hits' => [0],
                                ],
                                [
                                    'id' => 'o1',
                                    'site' => 1,
                                    'kind' => 'IfFalse',
                                    'line' => 5,
                                    'span' => null,
                                    'hits' => [1],
                                ],
                            ],
                            'loops' => [],
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * @return list<string>
     */
    private function filesIn(string $relative): array
    {
        $entries = scandir($this->directory->path . '/' . $relative);
        self::assertIsArray($entries);

        return array_values(array_map(
            static fn(string $entry): string => $relative . '/' . $entry,
            array_filter($entries, static fn(string $entry): bool => $entry !== '.' && $entry !== '..'),
        ));
    }
}
