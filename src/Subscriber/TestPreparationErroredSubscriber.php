<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Subscriber;

use OpCov\PHPUnit\Run;
use PHPUnit\Event\Test\PreparationErrored;
use PHPUnit\Event\Test\PreparationErroredSubscriber;

final readonly class TestPreparationErroredSubscriber implements PreparationErroredSubscriber
{
    public function __construct(
        private Run $run,
    ) {}

    #[\Override]
    public function notify(PreparationErrored $event): void
    {
        $this->run->testFailedOrErrored($event->test()->id());
    }
}
