<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Subscriber;

use OpCov\PHPUnit\Run;
use PHPUnit\Event\Application\Finished;
use PHPUnit\Event\Application\FinishedSubscriber;

final readonly class ApplicationFinishedSubscriber implements FinishedSubscriber
{
    public function __construct(
        private Run $run,
    ) {}

    #[\Override]
    public function notify(Finished $event): void
    {
        $this->run->applicationFinished();
    }
}
