<?php

declare(strict_types=1);

namespace OpCov\PHPUnit;

use LogicException;
use SebastianBergmann\CodeCoverage\Data\RawCodeCoverageData;
use SebastianBergmann\CodeCoverage\Driver\Driver as CodeCoverageDriver;

final class Driver extends CodeCoverageDriver
{
    private readonly Version $version;

    private readonly bool $collects;

    /**
     * @var array<string, true>
     */
    private array $modelledFiles = [];

    public function __construct(?Version $recordedBy = null)
    {
        $this->collects = $recordedBy === null;
        $this->version = $recordedBy ?? Handshake::verifyLoadedExtension();
    }

    // @mago-expect analysis:missing-override-attribute
    public function name(): string
    {
        return 'opcov';
    }

    // @mago-expect analysis:missing-override-attribute
    public function version(): string
    {
        return $this->version->toString();
    }

    #[\Override]
    public function nameAndVersion(): string
    {
        return $this->name() . ' ' . $this->version();
    }

    #[\Override]
    public function start(): void
    {
        if (!$this->collects) {
            throw new LogicException(sprintf(
                'this driver replays a report recorded by opcov %s and cannot collect',
                $this->version->toString(),
            ));
        }

        \opcov\start();
    }

    #[\Override]
    public function stop(): RawCodeCoverageData
    {
        $hits = \opcov\stop();
        \opcov\reset();

        $unmodelled = array_values(array_filter(
            array_keys($hits),
            fn(string $file): bool => !array_key_exists($file, $this->modelledFiles),
        ));
        $lines = [];

        foreach (\opcov\executable($unmodelled) as $file => $model) {
            $this->modelledFiles[$file] = true;
            foreach (array_keys($model['lines']) as $line) {
                $lines[$file][$line] = self::LINE_NOT_EXECUTED;
            }
        }

        foreach ($hits as $file => $hit) {
            foreach ($hit['lines'] as $line => $_) {
                $lines[$file][$line] = self::LINE_EXECUTED;
            }
        }

        return RawCodeCoverageData::fromLineCoverage($lines);
    }
}
