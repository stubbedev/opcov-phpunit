<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Subscriber;

use OpCov\PHPUnit\Run;
use PHPUnit\Event\Test\Prepared;
use PHPUnit\Event\Test\PreparedSubscriber;

final readonly class TestPreparedSubscriber implements PreparedSubscriber
{
    public function __construct(
        private Run $run,
    ) {}

    #[\Override]
    public function notify(Prepared $event): void
    {
        $this->run->testPrepared($event->test()->id());
    }
}
