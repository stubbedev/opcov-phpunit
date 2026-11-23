<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Subscriber;

use OpCov\PHPUnit\Run;
use PHPUnit\Event\Test\PreparationStarted;
use PHPUnit\Event\Test\PreparationStartedSubscriber;

final readonly class TestPreparationStartedSubscriber implements PreparationStartedSubscriber
{
    public function __construct(
        private Run $run,
    ) {}

    #[\Override]
    public function notify(PreparationStarted $event): void
    {
        $this->run->testPreparationStarted($event->test()->id());
    }
}
