<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Tests\Unit;

use LogicException;
use OpCov\PHPUnit\Lifecycle;
use PHPUnit\Framework\TestCase;

final class LifecycleMisuseTest extends TestCase
{
    public function testATestCannotStartWhileAnotherIsCollected(): void
    {
        $lifecycle = new Lifecycle();
        $lifecycle->begin('T::a');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageIsOrContains('test T::b started while the collection of test T::a had not ended');

        $lifecycle->begin('T::b');
    }

    public function testAnEventOfAnotherTestIsRefused(): void
    {
        $lifecycle = new Lifecycle();
        $lifecycle->begin('T::a');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageIsOrContains('an event of test T::b arrived while test T::a was being collected');

        $lifecycle->finished('T::b');
    }

    public function testTheRunCannotFinishWhileATestIsCollected(): void
    {
        $lifecycle = new Lifecycle();
        $lifecycle->begin('T::a');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageIsOrContains('the run finished while test T::a was still being collected');

        $lifecycle->assertIdle();
    }
}
