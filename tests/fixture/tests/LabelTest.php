<?php

declare(strict_types=1);

namespace Fixture\Tests;

use Fixture\Label;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LabelTest extends TestCase
{
    public function testFormatsANamedLabel(): void
    {
        self::assertSame('ADA, Oslo', Label::format('Ada', 'Oslo'));
    }

    public function testRefusesALabelWithoutCity(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Label::format(null, '');
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     */
    public static function parts(): iterable
    {
        yield 'none' => [[], ''];
        yield 'one' => [['a'], '/a'];
        yield 'two' => [['a', 'b'], '/a/b'];
    }

    /**
     * @param list<string> $parts
     */
    #[DataProvider('parts')]
    public function testJoinsAPath(array $parts, string $expected): void
    {
        self::assertSame($expected, Label::path($parts));
    }
}
