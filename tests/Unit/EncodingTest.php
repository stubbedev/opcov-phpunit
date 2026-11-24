<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Tests\Unit;

use InvalidArgumentException;
use OpCov\PHPUnit\Encoding;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EncodingTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function encodings(): iterable
    {
        yield 'ascii' => ['/srv/app/Foo.php', '/srv/app/Foo.php'];
        yield 'valid UTF-8' => ["/srv/caf\u{e9}/\u{20ac}.php", "/srv/caf\u{e9}/\u{20ac}.php"];
        yield 'latin-1 byte' => ["Caf\xE9::r\xE9sum\xE9", 'Caf%E9::r%E9sum%E9'];
        yield 'percent sign' => ['100%.php', '100%25.php'];
        yield 'escape-like text' => ['%E9', '%25E9'];
        yield 'truncated sequence' => ["\xE2\x82/", '%E2%82/'];
        yield 'surrogate' => ["\xED\xA0\x80", '%ED%A0%80'];
        yield 'overlong' => ["\xC0\xAF", '%C0%AF'];
        yield 'beyond U+10FFFF' => ["\xF4\x90\x80\x80", '%F4%90%80%80'];
        yield 'mixed' => ["\u{e9}\xE9", "\u{e9}%E9"];
    }

    #[DataProvider('encodings')]
    public function testBytesEncodeLikeOpcovAndDecodeBack(string $bytes, string $text): void
    {
        self::assertSame($text, Encoding::encode($bytes));
        self::assertSame($bytes, Encoding::decode($text, 'path'));
    }

    public function testEveryByteRoundTrips(): void
    {
        $bytes = '';

        for ($byte = 0; $byte < 256; $byte++) {
            $bytes .= chr($byte) . 'a' . chr(255 - $byte);
        }

        self::assertSame($bytes, Encoding::decode(Encoding::encode($bytes), 'path'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function nonCanonical(): iterable
    {
        yield 'lone percent' => ['%'];
        yield 'short escape' => ['%4'];
        yield 'lowercase hex' => ['%e9'];
        yield 'escaped ascii' => ['%41'];
        yield 'escaped valid UTF-8' => ['%C3%A9'];
    }

    #[DataProvider('nonCanonical')]
    public function testNonCanonicalTextIsRefused(string $text): void
    {
        $this->expectException(InvalidArgumentException::class);

        Encoding::decode($text, 'path');
    }
}
