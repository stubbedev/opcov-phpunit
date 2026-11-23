<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Subscriber;

use OpCov\PHPUnit\Run;
use PHPUnit\Event\Test\Errored;
use PHPUnit\Event\Test\ErroredSubscriber;

final readonly class TestErroredSubscriber implements ErroredSubscriber
{
    public function __construct(
        private Run $run,
    ) {}

    #[\Override]
    public function notify(Errored $event): void
    {
        $this->run->testFailedOrErrored($event->test()->id());
    }
}
