<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Subscriber;

use OpCov\PHPUnit\Run;
use PHPUnit\Event\Test\MarkedIncomplete;
use PHPUnit\Event\Test\MarkedIncompleteSubscriber;

final readonly class TestMarkedIncompleteSubscriber implements MarkedIncompleteSubscriber
{
    public function __construct(
        private Run $run,
    ) {}

    #[\Override]
    public function notify(MarkedIncomplete $event): void
    {
        $this->run->testSkippedOrIncomplete($event->test()->id());
    }
}
