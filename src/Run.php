<?php

declare(strict_types=1);

namespace OpCov\PHPUnit;

use Closure;
use Throwable;

final readonly class Run
{
    public function __construct(
        private Settings $settings,
        private Lifecycle $lifecycle,
    ) {}

    public function testPreparationStarted(string $test): void
    {
        $this->guard(sprintf('starting the collection of test %s', $test), function () use ($test): void {
            $this->lifecycle->begin($test);
            \opcov\start();
        });
    }

    public function testPrepared(string $test): void
    {
        $this->guard(sprintf('tracking test %s', $test), function () use ($test): void {
            $this->lifecycle->prepared($test);
        });
    }

    public function testConsideredRisky(string $test): void
    {
        $this->guard(sprintf('tracking test %s', $test), function () use ($test): void {
            $this->lifecycle->risky($test);
        });
    }

    public function testSkippedOrIncomplete(string $test): void
    {
        $this->guard(sprintf('ending the collection of test %s', $test), function () use ($test): void {
            $this->end($this->lifecycle->skippedOrIncomplete($test));
        });
    }

    public function testFailedOrErrored(string $test): void
    {
        $this->guard(sprintf('ending the collection of test %s', $test), function () use ($test): void {
            $this->end($this->lifecycle->failedOrErrored($test));
        });
    }

    public function testFinished(string $test): void
    {
        $this->guard(sprintf('ending the collection of test %s', $test), function () use ($test): void {
            $this->end($this->lifecycle->finished($test));
        });
    }

    public function applicationFinished(): void
    {
        $this->guard('writing the opcov report', function (): void {
            $this->lifecycle->assertIdle();
            \opcov\ensure_can_collect();
            $token = getenv('TEST_TOKEN');
            $path = $this->settings->output->resolve($token === false ? null : $token);
            ReportOutput::ensureDirectory($path);
            \opcov\write_report($path, $this->settings->reportOptions());
            ReportOutput::verifySchema($path);
        });
    }

    private function end(?Ending $ending): void
    {
        if ($ending === null) {
            return;
        }

        \opcov\stop($ending->attributedTo);
        \opcov\reset();
    }

    /**
     * @param Closure(): void $work
     */
    private function guard(string $attempt, Closure $work): void
    {
        try {
            $work();
        } catch (Throwable $cause) {
            Abort::because($attempt, $cause);
        }
    }
}
