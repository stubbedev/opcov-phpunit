<?php

declare(strict_types=1);

namespace OpCov\PHPUnit;

use InvalidArgumentException;
use PHPUnit\Runner\Extension\ParameterCollection;

final readonly class Settings
{
    private function __construct(
        public OutputPath $output,
        public ?string $root,
        public bool $absolutePaths,
    ) {}

    public static function fromParameters(ParameterCollection $parameters, string $baseDirectory): self
    {
        if (!$parameters->has('output')) {
            throw new InvalidArgumentException(
                'the extension needs an "output" parameter: <parameter name="output" value="build/opcov.json"/>',
            );
        }

        $root = null;

        if ($parameters->has('root')) {
            $root = self::absolute($parameters->get('root'), $baseDirectory);
        }

        $absolutePaths = false;

        if ($parameters->has('absolute_paths')) {
            $absolutePaths = self::boolean('absolute_paths', $parameters->get('absolute_paths'));
        }

        if ($absolutePaths && $root !== null) {
            throw new InvalidArgumentException(
                'the "root" and "absolute_paths" parameters exclude each other: absolute paths have no root',
            );
        }

        return new self(OutputPath::parse($parameters->get('output'), $baseDirectory), $root, $absolutePaths);
    }

    /**
     * @return array{root?: string, absolute_paths: bool}
     */
    public function reportOptions(): array
    {
        if ($this->root === null) {
            return ['absolute_paths' => $this->absolutePaths];
        }

        return ['root' => $this->root, 'absolute_paths' => $this->absolutePaths];
    }

    private static function absolute(string $path, string $baseDirectory): string
    {
        if ($path === '') {
            throw new InvalidArgumentException('the "root" parameter is empty');
        }

        $joined = str_starts_with($path, '/') ? $path : $baseDirectory . '/' . $path;
        $resolved = realpath($joined);

        if ($resolved === false || !is_dir($resolved)) {
            throw new InvalidArgumentException(sprintf(
                'the "root" parameter %s is not an existing directory',
                $joined,
            ));
        }

        return $resolved;
    }

    private static function boolean(string $name, string $value): bool
    {
        return match ($value) {
            'true' => true,
            'false' => false,
            default => throw new InvalidArgumentException(sprintf(
                'the "%s" parameter must be "true" or "false", got "%s"',
                $name,
                $value,
            )),
        };
    }
}
