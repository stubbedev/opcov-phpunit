<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Subscriber;

use OpCov\PHPUnit\Run;
use PHPUnit\Event\Test\Skipped;
use PHPUnit\Event\Test\SkippedSubscriber;

final readonly class TestSkippedSubscriber implements SkippedSubscriber
{
    public function __construct(
        private Run $run,
    ) {}

    #[\Override]
    public function notify(Skipped $event): void
    {
        $this->run->testSkippedOrIncomplete($event->test()->id());
    }
}
