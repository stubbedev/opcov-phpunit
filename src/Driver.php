<?php

declare(strict_types=1);

namespace OpCov\PHPUnit;

use LogicException;
use OpCov\PHPUnit\Branch\FileBranches;
use SebastianBergmann\CodeCoverage\Data\RawCodeCoverageData;
use SebastianBergmann\CodeCoverage\Driver\Driver as CodeCoverageDriver;
use SebastianBergmann\CodeCoverage\Driver\Granularity;

final class Driver extends CodeCoverageDriver
{
    private readonly Version $version;

    private readonly bool $collects;

    /**
     * @var array<string, FileBranches>
     */
    private array $models = [];

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

    // @mago-expect analysis:missing-override-attribute
    protected function canCollectBranchCoverage(): bool
    {
        return true;
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
        $raw = new RawCoverage($this->granularity() !== Granularity::Line);

        $unmodelled = array_values(array_filter(
            array_keys($hits),
            fn(string $file): bool => !array_key_exists($file, $this->models),
        ));

        foreach (\opcov\executable($unmodelled) as $file => $model) {
            $this->models[$file] = FileBranches::fromModel($model['functions'], $file, $file, 0);
            $raw->unexecuted($file, array_keys($model['lines']), $this->models[$file]);
        }

        foreach ($hits as $file => $hit) {
            $model = $this->modelOf($file);
            $raw->executed(
                $file,
                array_keys($hit['lines']),
                $model,
                $raw->branches ? $model->taken($hit['functions']) : [],
            );
        }

        return $raw->build();
    }

    private function modelOf(string $file): FileBranches
    {
        return (
            $this->models[$file] ?? throw new LogicException(sprintf(
                'opcov\\executable() did not describe %s, whose hits opcov\\stop() returned',
                $file,
            ))
        );
    }
}
