<?php

declare(strict_types=1);

namespace Fixture\Tests;

use Fixture\Label;
use Override;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class BrokenSetUpTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        Label::prepare();

        throw new RuntimeException('setUp breaks on purpose');
    }

    public function testNeverRuns(): void
    {
        self::fail('setUp throws first');
    }
}
