<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Tests\Unit;

use OpCov\PHPUnit\Ending;
use OpCov\PHPUnit\Lifecycle;
use PHPUnit\Framework\TestCase;

final class LifecycleTest extends TestCase
{
    public function testAPassingTestIsAttributedWhenItFinishes(): void
    {
        $lifecycle = new Lifecycle();
        $lifecycle->begin('T::a');
        $lifecycle->prepared('T::a');

        self::assertEquals(new Ending('T::a'), $lifecycle->finished('T::a'));
        $lifecycle->assertIdle();
    }

    public function testAFailingTestIsAttributedWhenItFinishes(): void
    {
        $lifecycle = new Lifecycle();
        $lifecycle->begin('T::a');
        $lifecycle->prepared('T::a');

        self::assertNull($lifecycle->failedOrErrored('T::a'));
        self::assertEquals(new Ending('T::a'), $lifecycle->finished('T::a'));
    }

    public function testATestSkippedInItsBodyIsDiscardedWhenItFinishes(): void
    {
        $lifecycle = new Lifecycle();
        $lifecycle->begin('T::a');
        $lifecycle->prepared('T::a');

        self::assertNull($lifecycle->skippedOrIncomplete('T::a'));
        self::assertEquals(new Ending(null), $lifecycle->finished('T::a'));
    }

    public function testARiskyTestIsDiscardedWhenItFinishes(): void
    {
        $lifecycle = new Lifecycle();
        $lifecycle->begin('T::a');
        $lifecycle->prepared('T::a');
        $lifecycle->risky('T::a');

        self::assertEquals(new Ending(null), $lifecycle->finished('T::a'));
    }

    public function testATestSkippedBeforeItWasPreparedEndsAtOnceAndIsDiscarded(): void
    {
        $lifecycle = new Lifecycle();
        $lifecycle->begin('T::a');

        self::assertEquals(new Ending(null), $lifecycle->skippedOrIncomplete('T::a'));
        self::assertNull($lifecycle->finished('T::a'));
        $lifecycle->assertIdle();
    }

    public function testATestWhoseSetUpFailedEndsAtOnceAndIsAttributed(): void
    {
        $lifecycle = new Lifecycle();
        $lifecycle->begin('T::a');

        self::assertEquals(new Ending('T::a'), $lifecycle->failedOrErrored('T::a'));
        self::assertNull($lifecycle->failedOrErrored('T::a'));
        self::assertNull($lifecycle->finished('T::a'));
        $lifecycle->assertIdle();
    }

    public function testEventsOfTestsThatWereNeverStartedAreIgnored(): void
    {
        $lifecycle = new Lifecycle();

        self::assertNull($lifecycle->skippedOrIncomplete('T::dependent'));
        self::assertNull($lifecycle->finished('T::dependent'));
        $lifecycle->assertIdle();
    }

    public function testEachTestIsCollectedOnItsOwn(): void
    {
        $lifecycle = new Lifecycle();
        $lifecycle->begin('T::a');
        $lifecycle->prepared('T::a');
        $lifecycle->risky('T::a');
        $lifecycle->finished('T::a');
        $lifecycle->begin('T::b');
        $lifecycle->prepared('T::b');

        self::assertEquals(new Ending('T::b'), $lifecycle->finished('T::b'));
    }
}
