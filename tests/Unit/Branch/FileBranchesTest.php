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

    public function testEveryCountingOutcomeIsABranchKeyedByItsOutcomeNumber(): void
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
                    ],
                    'paths' => [],
                ],
            ],
            self::model()->unexecuted(),
        );
    }

    public function testTakenOutcomesAreHitBranches(): void
    {
        self::assertSame(
            [
                'App\Foo->bar' => [
                    'branches' => [
                        0 => self::branch(site: 2, line: 13, hit: 0),
                        1 => self::branch(site: 2, line: 13, hit: 1),
                        4 => self::branch(site: 9, line: 17, hit: 0),
                        5 => self::branch(site: 9, line: 17, hit: 0),
                        6 => self::branch(site: 12, line: 23, hit: 1),
                    ],
                    'paths' => [],
                ],
            ],
            self::model()->coverage(['App\Foo::bar' => [1 => true, 2 => true, 6 => true], 'App\Foo::empty' => []]),
        );
    }

    public function testHitsAreGroupedByTest(): void
    {
        self::assertSame(
            [0 => ['App\Foo::bar' => [0 => true, 6 => true]], 2 => ['App\Foo::bar' => [6 => true]]],
            self::model(testCount: 3)->takenByTest(),
        );
    }

    /**
     * @return iterable<string, array{Closure(): mixed, string}>
     */
    public static function refusals(): iterable
    {
        yield 'outcome outside the model' => [
            static fn(): mixed => self::model()->coverage(['App\Foo::bar' => [7 => true]]),
            'App\Foo->bar has no outcome o7',
        ];
        yield 'function outside the model' => [
            static fn(): mixed => self::model()->coverage(['App\Foo::baz' => []]),
            'the function App\Foo::baz is not in the model of /srv/app/src/a.php',
        ];
        yield 'unknown kind' => [
            static fn(): FileBranches => self::single(self::outcome(0, 1, 'Sideways', 2)),
            '/srv/app/src/a.php functions.f.outcomes[0].kind is the unknown outcome kind "Sideways"',
        ];
        yield 'numbered kind without its number' => [
            static fn(): FileBranches => self::single(self::outcome(0, 1, 'MatchArm', 2)),
            'is the unknown outcome kind "MatchArm"',
        ];
        yield 'malformed id' => [
            static fn(): FileBranches => self::single(['id' => 'o01'] + self::outcome(0, 1, 'IfTrue', 2)),
            '/srv/app/src/a.php functions.f.outcomes[0].id "o01" is not an outcome id',
        ];
        yield 'hit outside the test table' => [
            static fn(): FileBranches => self::model(testCount: 2),
            'refers to a test index outside the test table',
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
     * @param array<string, mixed> $outcome
     */
    private static function single(array $outcome): FileBranches
    {
        return FileBranches::fromModel(['f' => ['outcomes' => [$outcome]]], self::FILE, self::FILE, 0);
    }

    private static function model(int $testCount = 0): FileBranches
    {
        $hits = $testCount === 0 ? [[], []] : [[0], [0, 2]];

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
                    'loops' => [],
                ],
                'App\Foo::empty' => ['span' => null, 'outcomes' => [self::outcome(0, 4, 'Engine', 30)], 'loops' => []],
            ],
            self::FILE,
            self::FILE,
            $testCount,
        );
    }
}
