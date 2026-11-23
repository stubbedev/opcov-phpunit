<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Tests\Unit\Branch;

use Closure;
use InvalidArgumentException;
use OpCov\PHPUnit\Branch\FileBranches;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FileBranchesTest extends TestCase
{
    private const string FILE = '/srv/app/src/a.php';

    public function testEveryCountingOutcomeAndLoopFindingIsABranchNumberedAfterTheOutcomes(): void
    {
        self::assertSame(
            [
                'App\Foo->bar' => [
                    'branches' => [
                        0 => self::branch(site: 2, line: 13, hit: 0),
                        1 => self::branch(site: 2, line: 13, hit: 0),
                        4 => self::branch(site: 9, line: 17, hit: 0),
                        5 => self::branch(site: 9, line: 17, hit: 0),
                        6 => self::branch(site: 12, line: 23, hit: 0),
                        7 => self::branch(site: 9, line: 17, hit: 0),
                        8 => self::branch(site: 9, line: 17, hit: 0),
                        10 => self::branch(site: 15, line: 25, hit: 0),
                    ],
                    'paths' => [],
                ],
                'App\Foo->loopOnly' => [
                    'branches' => [
                        0 => self::branch(site: 3, line: 31, hit: 0),
                        1 => self::branch(site: 3, line: 31, hit: 0),
                    ],
                    'paths' => [],
                ],
            ],
            self::model()->unexecuted(),
        );
    }

    public function testTakenOutcomesAndLoopFindingsAreHitBranches(): void
    {
        $model = self::model();

        self::assertSame(
            [
                'App\Foo->bar' => [
                    'branches' => [
                        0 => self::branch(site: 2, line: 13, hit: 0),
                        1 => self::branch(site: 2, line: 13, hit: 1),
                        4 => self::branch(site: 9, line: 17, hit: 0),
                        5 => self::branch(site: 9, line: 17, hit: 0),
                        6 => self::branch(site: 12, line: 23, hit: 1),
                        7 => self::branch(site: 9, line: 17, hit: 1),
                        8 => self::branch(site: 9, line: 17, hit: 0),
                        10 => self::branch(site: 15, line: 25, hit: 1),
                    ],
                    'paths' => [],
                ],
                'App\Foo->loopOnly' => [
                    'branches' => [
                        0 => self::branch(site: 3, line: 31, hit: 0),
                        1 => self::branch(site: 3, line: 31, hit: 1),
                    ],
                    'paths' => [],
                ],
            ],
            $model->coverage($model->taken([
                'App\Foo::bar' => [
                    'outcomes' => ['o1' => true, 'o2' => true, 'o6' => true],
                    'loops' => ['l0' => ['zero' => true], 'l1' => ['second' => true]],
                ],
                'App\Foo::empty' => ['outcomes' => [], 'loops' => []],
                'App\Foo::loopOnly' => ['outcomes' => [], 'loops' => ['l0' => ['second' => true]]],
            ])),
        );
    }

    public function testHitsAreGroupedByTest(): void
    {
        self::assertSame(
            [
                0 => ['App\Foo::bar' => [0 => true, 6 => true, 8 => true]],
                2 => ['App\Foo::bar' => [6 => true, 10 => true], 'App\Foo::loopOnly' => [0 => true]],
            ],
            self::model(testCount: 3)->takenByTest(),
        );
    }

    /**
     * @return iterable<string, array{Closure(): mixed, string}>
     */
    public static function refusals(): iterable
    {
        yield 'outcome outside the model' => [
            static fn(): mixed => self::model()
                ->taken(['App\Foo::bar' => ['outcomes' => ['o7' => true], 'loops' => []]]),
            'App\Foo->bar has no outcome o7',
        ];
        yield 'loop outside the model' => [
            static fn(): mixed => self::model()
                ->taken([
                    'App\Foo::bar' => ['outcomes' => [], 'loops' => ['l2' => ['second' => true]]],
                ]),
            'App\Foo->bar loop l2 has no finding "second"',
        ];
        yield 'zero finding of a loop that cannot run zero times' => [
            static fn(): mixed => self::model()
                ->taken([
                    'App\Foo::bar' => ['outcomes' => [], 'loops' => ['l1' => ['zero' => true]]],
                ]),
            'App\Foo->bar loop l1 has no finding "zero"',
        ];
        yield 'unknown loop finding' => [
            static fn(): mixed => self::model()
                ->taken([
                    'App\Foo::bar' => ['outcomes' => [], 'loops' => ['l0' => ['third' => true]]],
                ]),
            'App\Foo->bar loop l0 is the unknown loop finding "third"',
        ];
        yield 'function outside the model' => [
            static fn(): mixed => self::model()->coverage(['App\Foo::baz' => []]),
            'the function App\Foo::baz is not in the model of /srv/app/src/a.php',
        ];
        yield 'unknown kind' => [
            static fn(): FileBranches => self::single([self::outcome(0, 1, 'Sideways', 2)]),
            '/srv/app/src/a.php functions.f.outcomes[0].kind is the unknown outcome kind "Sideways"',
        ];
        yield 'numbered kind without its number' => [
            static fn(): FileBranches => self::single([self::outcome(0, 1, 'MatchArm', 2)]),
            'is the unknown outcome kind "MatchArm"',
        ];
        yield 'malformed outcome id' => [
            static fn(): FileBranches => self::single([['id' => 'o01'] + self::outcome(0, 1, 'IfTrue', 2)]),
            '/srv/app/src/a.php functions.f.outcomes[0].id "o01" is not an outcome id',
        ];
        yield 'malformed loop id' => [
            static fn(): FileBranches => self::single([], [['id' => 'o0'] + self::loop(0, 1, 2)]),
            '/srv/app/src/a.php functions.f.loops[0].id "o0" is not a loop id',
        ];
        yield 'missing loops' => [
            static fn(): FileBranches => FileBranches::fromModel(
                ['f' => ['outcomes' => []]],
                self::FILE,
                self::FILE,
                0,
            ),
            '/srv/app/src/a.php functions.f.loops is not an array',
        ];
        yield 'hit outside the test table' => [
            static fn(): FileBranches => self::model(testCount: 2),
            'refers to a test index outside the test table',
        ];
        yield 'loop hit outside the test table' => [
            static fn(): FileBranches => self::single([], [self::loop(0, 1, 2, second: [0])]),
            '/srv/app/src/a.php functions.f.loops[0].second refers to a test index outside the test table',
        ];
    }

    /**
     * @param Closure(): mixed $refused
     */
    #[DataProvider('refusals')]
    public function testMalformedDataIsRefused(Closure $refused, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains($message);

        $refused();
    }

    /**
     * @return array{op_start: int, op_end: int, line_start: int, line_end: int, hit: int, out: array<never, never>, out_hit: array<never, never>}
     */
    private static function branch(int $site, int $line, int $hit): array
    {
        return [
            'op_start' => $site,
            'op_end' => $site,
            'line_start' => $line,
            'line_end' => $line,
            'hit' => $hit,
            'out' => [],
            'out_hit' => [],
        ];
    }

    /**
     * @param list<int> $hits
     *
     * @return array<string, mixed>
     */
    private static function outcome(int $number, int $site, mixed $kind, int $line, array $hits = []): array
    {
        return [
            'id' => 'o' . $number,
            'site' => $site,
            'kind' => $kind,
            'line' => $line,
            'span' => null,
            'hits' => $hits,
        ];
    }

    /**
     * @param null|list<int> $zero
     * @param list<int> $second
     *
     * @return array<string, mixed>
     */
    private static function loop(int $number, int $site, int $line, ?array $zero = [], array $second = []): array
    {
        return [
            'id' => 'l' . $number,
            'site' => $site,
            'kind' => $zero === null ? 'PostTest' : 'Foreach',
            'line' => $line,
            'zero' => $zero,
            'second' => $second,
        ];
    }

    /**
     * @param list<array<string, mixed>> $outcomes
     * @param list<array<string, mixed>> $loops
     */
    private static function single(array $outcomes, array $loops = []): FileBranches
    {
        return FileBranches::fromModel(
            ['f' => ['outcomes' => $outcomes, 'loops' => $loops]],
            self::FILE,
            self::FILE,
            0,
        );
    }

    private static function model(int $testCount = 0): FileBranches
    {
        $hits = $testCount === 0 ? [[], [], []] : [[0], [0, 2], [2]];

        return FileBranches::fromModel(
            [
                '{main}@' . self::FILE => ['span' => null, 'outcomes' => [], 'loops' => []],
                'App\Foo::bar' => [
                    'span' => null,
                    'outcomes' => [
                        self::outcome(0, 2, 'CoalesceFallback', 13, $hits[0]),
                        self::outcome(1, 2, 'CoalesceValue', 13),
                        self::outcome(2, 7, 'LoopEnter', 17, $hits[0]),
                        self::outcome(3, 7, 'LoopSkip', 17),
                        self::outcome(4, 9, 'LoopBack', 17),
                        self::outcome(5, 9, 'LoopExit', 17),
                        self::outcome(6, 12, ['MatchArm' => 0], 23, $hits[1]),
                    ],
                    'loops' => [
                        self::loop(0, 9, 17, second: $hits[0]),
                        self::loop(1, 15, 25, zero: null, second: $hits[2]),
                    ],
                ],
                'App\Foo::empty' => ['span' => null, 'outcomes' => [self::outcome(0, 4, 'Engine', 30)], 'loops' => []],
                'App\Foo::loopOnly' => [
                    'span' => null,
                    'outcomes' => [],
                    'loops' => [self::loop(0, 3, 31, zero: $hits[2])],
                ],
            ],
            self::FILE,
            self::FILE,
            $testCount,
        );
    }
}
