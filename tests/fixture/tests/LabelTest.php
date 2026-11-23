<?php

declare(strict_types=1);

namespace Fixture\Tests;

use Fixture\Label;
use InvalidArgumentException;
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
}
