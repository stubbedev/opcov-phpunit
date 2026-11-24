<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Tests\Integration;

use OpCov\PHPUnit\Abort;
use PHPUnit\Framework\TestCase;

final class RunnersTest extends TestCase
{
    private const string PHPUNIT = 'vendor/bin/phpunit';

    private const string PEST = 'vendor/bin/pest';

    private const array RUNNERS = [
        'phpunit13' => self::PHPUNIT,
        'pest4' => self::PEST,
        'pest5' => self::PEST,
    ];

    private const int PEST_FAILURES = 2;

    public function testEveryRunnerWritesTheSameReportUpToTestIdFormatting(): void
    {
        $expected = $this->normalizedReport('phpunit12', self::PHPUNIT);

        foreach (self::RUNNERS as $name => $runner) {
            self::assertSame($expected, $this->normalizedReport($name, $runner), $name);
        }
    }

    public function testAttributionFollowsTheTestOutcome(): void
    {
        $report = $this->runFixture('phpunit12', self::PHPUNIT);
        $hits = $report->hitsByLine();
        $tests = $report->tests();

        self::assertSame(
            [
                'Fixture\Tests\BrokenSetUpTest::testNeverRuns',
                'Fixture\Tests\LabelTest::testFormatsANamedLabel',
                'Fixture\Tests\LabelTest::testJoinsAPath#none',
                'Fixture\Tests\LabelTest::testJoinsAPath#one',
                'Fixture\Tests\LabelTest::testJoinsAPath#two',
                'Fixture\Tests\LabelTest::testRefusesALabelWithoutCity',
                'Fixture\Tests\ShippingTest::testCostDependsOnWeight#heavy',
                'Fixture\Tests\ShippingTest::testCostDependsOnWeight#light',
                'Fixture\Tests\ShippingTest::testExpressFailsOnPurpose',
                'Fixture\Tests\ShippingTest::testLargeOrdersShipForFree',
            ],
            $tests,
        );
        self::assertSame(
            ['Fixture\Tests\ShippingTest::testLargeOrdersShipForFree'],
            $hits['src/Shipping.php'][12] ?? null,
        );
        self::assertSame(
            ['Fixture\Tests\ShippingTest::testExpressFailsOnPurpose'],
            $hits['src/Shipping.php'][32] ?? null,
        );
        self::assertSame(
            ['Fixture\Tests\ShippingTest::testCostDependsOnWeight#light'],
            $hits['src/Shipping.php'][24] ?? null,
        );
        self::assertSame(['Fixture\Tests\BrokenSetUpTest::testNeverRuns'], $hits['src/Label.php'][24] ?? null);
        self::assertSame(['Fixture\Tests\LabelTest::testRefusesALabelWithoutCity'], $hits['src/Label.php'][16] ?? null);
    }

    public function testParallelWorkersEachWriteTheirOwnReport(): void
    {
        $fixture = Fixture::runner('phpunit12');
        $fixture->clean();
        $process = $fixture->launch(['vendor/bin/paratest', '-c', 'paratest.xml', '-p', '3']);

        self::assertNotSame(Abort::EXIT_CODE, $process->exitCode, $process->describe());
        $tests = [];

        foreach (['1', '2', '3'] as $token) {
            $tests = [...$tests, ...$fixture->report(sprintf('opcov.%s.json', $token))->tests()];
        }

        sort($tests);
        self::assertSame($this->runFixture('phpunit12', self::PHPUNIT)->tests(), $tests);
    }

    public function testARunWithoutTheExtensionFails(): void
    {
        $process = Fixture::runner('phpunit12')->php([self::PHPUNIT]);

        self::assertSame(Abort::EXIT_CODE, $process->exitCode, $process->describe());
        self::assertStringContainsString('the opcov extension is not loaded', $process->errors);
    }

    public function testARunWithCollectionOffFails(): void
    {
        $fixture = Fixture::runner('phpunit12');
        $process = $fixture->php(['-d', $fixture->extensionSetting(), self::PHPUNIT]);

        self::assertSame(Abort::EXIT_CODE, $process->exitCode, $process->describe());
        self::assertStringContainsString('opcov.enabled was 0 when PHP started', $process->errors);
    }

    /**
     * @return array<array-key, mixed>
     */
    private function normalizedReport(string $name, string $runner): array
    {
        return $this->runFixture($name, $runner)->withTestNames(self::canonicalTestName(...))->document;
    }

    private function runFixture(string $name, string $runner): Report
    {
        $fixture = Fixture::runner($name);
        $fixture->clean();
        $process = $fixture->launch([$runner]);
        $expected = $runner === self::PEST ? self::PEST_FAILURES : Fixture::PHPUNIT_ERRORS;

        self::assertSame($expected, $process->exitCode, $process->describe());

        return $fixture->report('opcov.json');
    }

    private static function canonicalTestName(string $test): string
    {
        $match = [];
        $pest = preg_match('/\AP\\\\Tests\\\\(\w+)::__pest_evaluable_(\w+?)(?:#dataset "(.*)")?\z/', $test, $match);

        if ($pest === 1) {
            return sprintf('%s::%s#%s', $match[1] ?? '', $match[2] ?? '', $match[3] ?? '');
        }

        $phpunit = preg_match('/\AFixture\\\\Tests\\\\(\w+)::test(\w+?)(?:#(.*))?\z/', $test, $match);
        self::assertSame(1, $phpunit, sprintf('unexpected test id %s', $test));
        $words = preg_replace('/(?<!^)[A-Z]/', replacement: '_$0', subject: $match[2] ?? '');

        return sprintf('%s::%s#%s', $match[1] ?? '', strtolower((string) $words), $match[3] ?? '');
    }
}
