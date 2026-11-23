<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Subscriber;

use OpCov\PHPUnit\Run;
use PHPUnit\Event\Test\PreparationFailed;
use PHPUnit\Event\Test\PreparationFailedSubscriber;

final readonly class TestPreparationFailedSubscriber implements PreparationFailedSubscriber
{
    public function __construct(
        private Run $run,
    ) {}

    #[\Override]
    public function notify(PreparationFailed $event): void
    {
        $this->run->testFailedOrErrored($event->test()->id());
    }
}
