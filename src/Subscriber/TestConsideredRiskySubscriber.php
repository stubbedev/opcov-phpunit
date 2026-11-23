<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Subscriber;

use OpCov\PHPUnit\Run;
use PHPUnit\Event\Test\ConsideredRisky;
use PHPUnit\Event\Test\ConsideredRiskySubscriber;

final readonly class TestConsideredRiskySubscriber implements ConsideredRiskySubscriber
{
    public function __construct(
        private Run $run,
    ) {}

    #[\Override]
    public function notify(ConsideredRisky $event): void
    {
        $this->run->testConsideredRisky($event->test()->id());
    }
}
