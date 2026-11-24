<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Tests\Unit\Cov;

use OpCov\PHPUnit\Cov\EvalEntry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EvalEntryTest extends TestCase
{
    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function names(): iterable
    {
        yield 'eval entry' => ["src/a.php(12) : eval()'d code #0123456789abcdef", true];
        yield 'nested eval entry' => ["/srv/a.php(3) : eval()'d code(1) : eval()'d code #0123456789abcdef", true];
        yield 'host with parentheses' => ["/srv/a (copy)/x(1).php(4) : eval()'d code #0123456789abcdef", true];
        yield 'plain file' => ['src/a.php', false];
        yield 'file with parentheses' => ['src/a(1).php', false];
        yield 'no line' => ["src/a.php() : eval()'d code", false];
        yield 'no description' => ['src/a.php(3) : ', false];
        yield 'no host' => ["(3) : eval()'d code", false];
        yield 'last separator without a line' => ["src/a.php(3) : x(y) : eval()'d code", false];
    }

    #[DataProvider('names')]
    public function testRecognisesTheNamesOfEvalEntries(string $path, bool $eval): void
    {
        self::assertSame($eval, EvalEntry::is($path));
    }
}
