<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Launcher;

use OpCov\PHPUnit\AtomicFile;
use RuntimeException;

final readonly class RunDirectory
{
    private function __construct(
        public string $path,
    ) {}

    public static function forRun(string $temporaryDirectory, int $userId, string $identity): self
    {
        $user = sprintf('%s/opcov-%d', $temporaryDirectory, $userId);
        self::ensurePrivate($user, $userId);
        $path = sprintf('%s/%s', $user, hash('sha256', $identity));
        self::ensurePrivate($path, $userId);

        return new self($path);
    }

    /**
     * @param list<IniSetting> $phpSettings
     */
    public function writeSettings(SourcePaths $source, array $phpSettings): void
    {
        AtomicFile::write(
            $this->path . '/opcov.ini',
            implode('', array_map(static fn(IniSetting $setting): string => $setting->toLine(), $phpSettings))
                . IniSetting::line('opcov.enabled', '1')
                . IniSetting::line('opcov.collect_paths', $this->listfile('collect_paths', $source->include))
                . IniSetting::line('opcov.exclude_paths', $this->listfile('exclude_paths', $source->exclude)),
        );
    }

    /**
     * @param list<string> $paths
     */
    private function listfile(string $setting, array $paths): string
    {
        if ($paths === []) {
            return '';
        }

        $file = sprintf('%s/%s.txt', $this->path, $setting);
        AtomicFile::write($file, implode("\n", $paths) . "\n");

        return '@' . $file;
    }

    private static function ensurePrivate(string $path, int $userId): void
    {
        if (!is_dir($path) && !mkdir($path, permissions: 0o700) && !is_dir($path)) {
            throw new RuntimeException(sprintf('cannot create the run directory %s', $path));
        }

        $status = lstat($path);

        if ($status === false) {
            throw new RuntimeException(sprintf('cannot inspect the run directory %s', $path));
        }

        $isDirectory = ($status['mode'] & 0o170_000) === 0o040_000;

        if (!$isDirectory || $status['uid'] !== $userId || ($status['mode'] & 0o077) !== 0) {
            throw new RuntimeException(sprintf(
                'the run directory %s must be a directory owned by user %d and accessible only to it (mode 0700); remove it and retry',
                $path,
                $userId,
            ));
        }
    }
}
