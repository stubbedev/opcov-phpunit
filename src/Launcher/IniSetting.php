<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Launcher;

use InvalidArgumentException;

final readonly class IniSetting
{
    private function __construct(
        private string $name,
        private string $value,
    ) {}

    public static function parse(string $setting): self
    {
        $parts = explode('=', $setting, limit: 2);
        $name = $parts[0];
        $value = $parts[1] ?? '1';

        if (preg_match('/\A[A-Za-z0-9_.]+\z/', $name) !== 1) {
            throw new InvalidArgumentException(sprintf('-d %s does not name an INI setting', $setting));
        }

        if (str_starts_with($name, 'opcov.')) {
            throw new InvalidArgumentException(sprintf(
                '-d %s: the launcher sets the opcov settings from the PHPUnit configuration; set them yourself without the launcher instead',
                $setting,
            ));
        }

        return new self($name, self::quotable($value, $setting));
    }

    public static function quotable(string $value, string $what): string
    {
        if (strpbrk($value, characters: "'\n\r") !== false) {
            throw new InvalidArgumentException(sprintf(
                '%s contains a single quote or a line break, which an INI file cannot hold verbatim',
                $what,
            ));
        }

        return $value;
    }

    public static function line(string $name, string $value): string
    {
        return sprintf("%s='%s'\n", $name, self::quotable($value, $name));
    }

    public function toLine(): string
    {
        return self::line($this->name, $this->value);
    }
}
