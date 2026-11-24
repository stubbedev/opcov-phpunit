<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Tests\Unit\Branch;

use InvalidArgumentException;
use OpCov\PHPUnit\Branch\FunctionName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FunctionNameTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function keys(): iterable
    {
        yield 'top-level code' => ['{main}@src/a.php', '{main}'];
        yield 'function' => ['App\Support\helper', 'App\Support\helper'];
        yield 'method' => ['App\Foo::bar', 'App\Foo->bar'];
        yield 'property hook' => ['App\Foo::$name::get', 'App\Foo->$name::get'];
        yield 'closure' => ['{closure}@src/a.php:24-26', '{closure:/srv/app/src/a.php:24-26}'];
        yield 'closure with an ordinal' => ['{closure}@src/a.php:24-24#1', '{closure:/srv/app/src/a.php:24-24}#1'];
        yield 'anonymous class method' => [
            'class@anonymous@src/a.php:3-9#1::run',
            'class@anonymous@/srv/app/src/a.php:3-9#1->run',
        ];
        yield 'anonymous class property hook' => [
            'class@anonymous@src/a.php:3-9::$size::set',
            'class@anonymous@/srv/app/src/a.php:3-9->$size::set',
        ];
    }

    #[DataProvider('keys')]
    public function testFunctionKeysMapToPhpCodeCoverageNames(string $key, string $name): void
    {
        self::assertSame($name, FunctionName::of($key, 'src/a.php', '/srv/app/src/a.php'));
    }

    public function testAbsoluteKeysNameTheFileAsIs(): void
    {
        self::assertSame('{closure:/srv/app/src/a.php:2-3}', FunctionName::of(
            '{closure}@/srv/app/src/a.php:2-3',
            '/srv/app/src/a.php',
            '/srv/app/src/a.php',
        ));
    }

    public function testKeysInCanonicalByteEncodingMapToTheRawNames(): void
    {
        $file = "/srv/caf\xE9 100%.php";

        self::assertSame("Caf\xE9->r\xE9sum\xE9", FunctionName::of('Caf%E9::r%E9sum%E9', $file, $file));
        self::assertSame('{main}', FunctionName::of('{main}@/srv/caf%E9 100%25.php', $file, $file));
        self::assertSame(
            sprintf('{closure:%s:3-4}', $file),
            FunctionName::of('{closure}@/srv/caf%E9 100%25.php:3-4', $file, $file),
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function malformedKeys(): iterable
    {
        yield 'empty' => [''];
        yield 'top-level code of another file' => ['{main}@src/b.php'];
        yield 'closure of another file' => ['{closure}@src/b.php:1-2'];
        yield 'closure without lines' => ['{closure}@src/a.php:'];
        yield 'closure with ordinal 0' => ['{closure}@src/a.php:1-2#0'];
        yield 'anonymous class without member' => ['class@anonymous@src/a.php:3-9'];
        yield 'unknown hook' => ['App\Foo::$name::unset'];
        yield 'non-canonical escape' => ['Caf%e9::run'];
    }

    #[DataProvider('malformedKeys')]
    public function testMalformedKeysAreRefused(string $key): void
    {
        $this->expectException(InvalidArgumentException::class);

        FunctionName::of($key, 'src/a.php', '/srv/app/src/a.php');
    }
}
