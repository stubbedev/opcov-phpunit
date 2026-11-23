<?php

declare(strict_types=1);

namespace OpCov\PHPUnit;

use PHPUnit\Runner\Extension\Extension as PHPUnitExtension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use RuntimeException;
use Throwable;

final class Extension implements PHPUnitExtension
{
    #[\Override]
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        try {
            $settings = Settings::fromParameters($parameters, self::baseDirectory($configuration));
            Handshake::verifyLoadedExtension();
            \opcov\ensure_can_collect();
        } catch (Throwable $cause) {
            Abort::because('starting opcov', $cause);
        }

        $run = new Run($settings, new Lifecycle());

        $facade->registerSubscribers(
            new Subscriber\TestPreparationStartedSubscriber($run),
            new Subscriber\TestPreparedSubscriber($run),
            new Subscriber\TestConsideredRiskySubscriber($run),
            new Subscriber\TestSkippedSubscriber($run),
            new Subscriber\TestMarkedIncompleteSubscriber($run),
            new Subscriber\TestPreparationErroredSubscriber($run),
            new Subscriber\TestPreparationFailedSubscriber($run),
            new Subscriber\TestFailedSubscriber($run),
            new Subscriber\TestErroredSubscriber($run),
            new Subscriber\TestFinishedSubscriber($run),
            new Subscriber\ApplicationFinishedSubscriber($run),
        );
    }

    private static function baseDirectory(Configuration $configuration): string
    {
        if ($configuration->hasConfigurationFile()) {
            return dirname($configuration->configurationFile());
        }

        $cwd = getcwd();

        if ($cwd === false) {
            throw new RuntimeException(
                'cannot determine the working directory to resolve the extension parameters against',
            );
        }

        return $cwd;
    }
}
