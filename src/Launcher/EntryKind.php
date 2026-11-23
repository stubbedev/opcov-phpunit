<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Launcher;

enum EntryKind: string
{
    case Directory = 'directory';
    case File = 'file';

    public function globFlags(): int
    {
        return match ($this) {
            self::Directory => GLOB_ONLYDIR,
            self::File => 0,
        };
    }

    public function matches(string $path): bool
    {
        return match ($this) {
            self::Directory => is_dir($path),
            self::File => is_file($path),
        };
    }
}
