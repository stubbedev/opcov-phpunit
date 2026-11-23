<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Tests\Integration;

use OpCov\PHPUnit\Tests\Unit\TemporaryDirectory;
use PHPUnit\Framework\TestCase;

final class PhpCodeCoverageTest extends TestCase
{
    private const string FORMATS = 'Fixture\Tests\LabelTest::testFormatsANamedLabel';

    private const string REFUSES = 'Fixture\Tests\LabelTest::testRefusesALabelWithoutCity';

    private const array LABEL_FORMAT_BRANCHES = [
        [13, [self::REFUSES]],
        [13, [self::FORMATS]],
        [15, [self::REFUSES]],
        [15, [self::FORMATS]],
    ];

    private const string JOINS_NONE = 'Fixture\Tests\LabelTest::testJoinsAPath#none';

    private const string JOINS_ONE = 'Fixture\Tests\LabelTest::testJoinsAPath#one';

    private const string JOINS_TWO = 'Fixture\Tests\LabelTest::testJoinsAPath#two';

    private const array LABEL_PATH_BRANCHES = [
        2 => [34, [self::JOINS_ONE, self::JOINS_TWO]],
        3 => [34, [self::JOINS_NONE, self::JOINS_ONE, self::JOINS_TWO]],
        4 => [34, [self::JOINS_NONE]],
        5 => [34, [self::JOINS_TWO]],
    ];

    public function testTheCovFileCarriesLinesAndAttributionForPhpCodeCoverage12(): void
    {
        $fixture = Fixture::runner('phpunit12');
        $fixture->clean();
        $process = $fixture->launch(['vendor/bin/phpunit']);
        self::assertSame(Fixture::PHPUNIT_ERRORS, $process->exitCode, $process->describe());
        $convert = $fixture->php(['vendor/bin/opcov-cov', 'build/opcov.json', 'build/opcov.cov']);
        self::assertSame(0, $convert->exitCode, $convert->describe());

        $read = $fixture->php(['-r', <<<'PHP'
            require 'vendor/autoload.php';
            $coverage = require 'build/opcov.cov';
            $lines = [];
            foreach ($coverage->getData(true)->lineCoverage() as $file => $fileLines) {
                $lines[basename($file)] = $fileLines;
            }
            $branches = [];
            foreach ($coverage->getData(true)->functionCoverage() as $functions) {
                foreach (['Fixture\Label->format', 'Fixture\Label->path'] as $name) {
                    foreach (($functions[$name] ?? null)?->branches ?? [] as $id => $branch) {
                        $branches[$name][$id] = [$branch->line_start, $branch->hit];
                    }
                }
            }
            echo json_encode([
                'class' => $coverage::class,
                'tests' => array_keys($coverage->getTests()),
                'shipping12' => $lines['Shipping.php'][12],
                'label13' => $lines['Label.php'][13],
                'labelFormatBranches' => $branches['Fixture\Label->format'],
                'labelPathBranches' => $branches['Fixture\Label->path'],
            ]);
            PHP]);
        self::assertSame(0, $read->exitCode, $read->describe());
        self::assertSame(
            [
                'class' => 'SebastianBergmann\CodeCoverage\CodeCoverage',
                'tests' => [
                    'Fixture\Tests\BrokenSetUpTest::testNeverRuns',
                    'Fixture\Tests\LabelTest::testFormatsANamedLabel',
                    self::JOINS_NONE,
                    self::JOINS_ONE,
                    self::JOINS_TWO,
                    'Fixture\Tests\LabelTest::testRefusesALabelWithoutCity',
                    'Fixture\Tests\ShippingTest::testCostDependsOnWeight#heavy',
                    'Fixture\Tests\ShippingTest::testCostDependsOnWeight#light',
                    'Fixture\Tests\ShippingTest::testExpressFailsOnPurpose',
                    'Fixture\Tests\ShippingTest::testLargeOrdersShipForFree',
                ],
                'shipping12' => ['Fixture\Tests\ShippingTest::testLargeOrdersShipForFree'],
                'label13' => [self::FORMATS, self::REFUSES],
                'labelFormatBranches' => self::LABEL_FORMAT_BRANCHES,
                'labelPathBranches' => self::LABEL_PATH_BRANCHES,
            ],
            json_decode($read->output, associative: true, flags: JSON_THROW_ON_ERROR),
            $read->describe(),
        );
    }

    public function testThePhpUnit13DriverFeedsPhpCodeCoverage(): void
    {
        $fixture = Fixture::runner('phpunit13');
        $fixture->clean();
        $process = $fixture->launch(['vendor/bin/phpunit', '-c', 'driver.xml', '--coverage-php', 'build/driver.cov']);
        self::assertSame(Fixture::PHPUNIT_ERRORS, $process->exitCode, $process->describe());

        $read = $fixture->php(['-r', <<<'PHP'
            require 'vendor/autoload.php';
            $data = (new SebastianBergmann\CodeCoverage\Serialization\Unserializer())->unserialize('build/driver.cov');
            $ids = $data['codeCoverage']->testIds();
            $lines = [];
            foreach ($data['codeCoverage']->lineCoverage() as $file => $fileLines) {
                foreach ($fileLines as $line => $tests) {
                    $lines[basename($file)][$line] = array_map(static fn (int $index): string => $ids[$index], array_keys($tests ?? []));
                }
            }
            echo json_encode([
                'driver' => $data['buildInformation']['phpCodeCoverage']['driverInformation']['name'],
                'shipping12' => $lines['Shipping.php'][12],
                'shipping32' => $lines['Shipping.php'][32],
            ]);
            PHP]);
        self::assertSame(0, $read->exitCode, $read->describe());
        self::assertSame(
            [
                'driver' => 'opcov',
                'shipping12' => ['Fixture\Tests\ShippingTest::testLargeOrdersShipForFree'],
                'shipping32' => ['Fixture\Tests\ShippingTest::testExpressFailsOnPurpose'],
            ],
            json_decode($read->output, associative: true, flags: JSON_THROW_ON_ERROR),
            $read->describe(),
        );
    }

    public function testThePhpUnit13DriverReportsOutcomesAsBranches(): void
    {
        $fixture = Fixture::runner('phpunit13');
        $fixture->clean();
        $html = TemporaryDirectory::create();

        try {
            $process = $fixture->launch([
                'vendor/bin/phpunit',
                '-c',
                'driver.xml',
                '--branch-coverage',
                '--coverage-php',
                'build/branches.cov',
                '--coverage-html',
                $html->path,
            ]);
            self::assertSame(Fixture::PHPUNIT_ERRORS, $process->exitCode, $process->describe());
            self::assertFileExists($html->path . '/Label.php_branch.html');
        } finally {
            $html->remove();
        }

        $read = $fixture->php(['-r', <<<'PHP'
            require 'vendor/autoload.php';
            $data = (new SebastianBergmann\CodeCoverage\Serialization\Unserializer())->unserialize('build/branches.cov');
            $ids = $data['codeCoverage']->testIds();
            $branches = [];
            foreach ($data['codeCoverage']->functionCoverage() as $file => $functions) {
                foreach ($functions as $name => $function) {
                    foreach ($function->branches as $id => $branch) {
                        $tests = array_map(static fn (int $index): string => $ids[$index], array_keys($branch->hit));
                        sort($tests);
                        $branches[$name][$id] = [$branch->line_start, $tests];
                    }
                }
            }
            echo json_encode([
                'labelFormat' => $branches['Fixture\Label->format'],
                'labelPath' => $branches['Fixture\Label->path'],
                'shippingCost' => array_keys($branches['Fixture\Shipping->cost']),
            ]);
            PHP]);
        self::assertSame(0, $read->exitCode, $read->describe());
        self::assertSame(
            [
                'labelFormat' => self::LABEL_FORMAT_BRANCHES,
                'labelPath' => self::LABEL_PATH_BRANCHES,
                'shippingCost' => [0, 1, 4, 5, 6, 7, 8, 9, 10, 11, 12],
            ],
            json_decode($read->output, associative: true, flags: JSON_THROW_ON_ERROR),
            $read->describe(),
        );
    }

    public function testThePhpUnit13DriverRefusesPathCoverage(): void
    {
        $fixture = Fixture::runner('phpunit13');
        $fixture->clean();
        $process = $fixture->launch([
            'vendor/bin/phpunit',
            '-c',
            'driver.xml',
            '--path-coverage',
            '--coverage-php',
            'build/paths.cov',
        ]);

        self::assertStringContainsString('does not support path coverage', $process->output, $process->describe());
        self::assertFileDoesNotExist($fixture->runnerDirectory . '/build/paths.cov');
    }
}
