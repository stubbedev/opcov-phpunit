<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Subscriber;

use OpCov\PHPUnit\Run;
use PHPUnit\Event\Test\Finished;
use PHPUnit\Event\Test\FinishedSubscriber;

final readonly class TestFinishedSubscriber implements FinishedSubscriber
{
    public function __construct(
        private Run $run,
    ) {}

    #[\Override]
    public function notify(Finished $event): void
    {
        $this->run->testFinished($event->test()->id());
    }
}
