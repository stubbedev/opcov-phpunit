<?php

declare(strict_types=1);

namespace OpCov\PHPUnit;

use LogicException;

final class Lifecycle
{
    private ?string $test = null;

    private bool $prepared = false;

    private bool $discarded = false;

    public function begin(string $test): void
    {
        if ($this->test !== null) {
            throw new LogicException(sprintf(
                'test %s started while the collection of test %s had not ended',
                $test,
                $this->test,
            ));
        }

        $this->test = $test;
        $this->prepared = false;
        $this->discarded = false;
    }

    public function prepared(string $test): void
    {
        if (!$this->isCurrent($test)) {
            return;
        }

        $this->prepared = true;
    }

    public function risky(string $test): void
    {
        if (!$this->isCurrent($test)) {
            return;
        }

        $this->discarded = true;
    }

    public function skippedOrIncomplete(string $test): ?Ending
    {
        if (!$this->isCurrent($test)) {
            return null;
        }

        $this->discarded = true;

        if ($this->prepared) {
            return null;
        }

        return $this->end();
    }

    public function failedOrErrored(string $test): ?Ending
    {
        if (!$this->isCurrent($test) || $this->prepared) {
            return null;
        }

        return $this->end();
    }

    public function finished(string $test): ?Ending
    {
        if (!$this->isCurrent($test)) {
            return null;
        }

        return $this->end();
    }

    public function assertIdle(): void
    {
        if ($this->test !== null) {
            throw new LogicException(sprintf('the run finished while test %s was still being collected', $this->test));
        }
    }

    private function isCurrent(string $test): bool
    {
        if ($this->test === null) {
            return false;
        }

        if ($this->test !== $test) {
            throw new LogicException(sprintf(
                'an event of test %s arrived while test %s was being collected',
                $test,
                $this->test,
            ));
        }

        return true;
    }

    private function end(): Ending
    {
        $ending = new Ending($this->discarded ? null : $this->test);
        $this->test = null;

        return $ending;
    }
}
