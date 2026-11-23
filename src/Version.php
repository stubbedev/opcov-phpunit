<?php

declare(strict_types=1);

namespace OpCov\PHPUnit;

use InvalidArgumentException;

final readonly class Version
{
    /**
     * @param non-empty-string $text
     */
    private function __construct(
        public int $major,
        public int $minor,
        public int $patch,
        private string $text,
    ) {}

    public static function parse(string $text): self
    {
        if ($text === '') {
            throw new InvalidArgumentException('the version is empty');
        }

        $parts = [];

        if (
            preg_match(
                '/\A(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-[0-9A-Za-z.-]+)?(?:\+[0-9A-Za-z.-]+)?\z/',
                $text,
                $parts,
            ) !== 1
        ) {
            throw new InvalidArgumentException(sprintf('"%s" is not a semantic version', $text));
        }

        return new self(self::number($parts, 1), self::number($parts, 2), self::number($parts, 3), $text);
    }

    /**
     * @param array<array-key, string> $parts
     */
    private static function number(array $parts, int $group): int
    {
        return (int) (
            $parts[$group] ?? throw new InvalidArgumentException(sprintf('version group %d is missing', $group))
        );
    }

    public function compatibilityLine(): string
    {
        if ($this->major === 0) {
            return sprintf('0.%d', $this->minor);
        }

        return (string) $this->major;
    }

    public function isCompatibleWith(self $other): bool
    {
        return $this->compatibilityLine() === $other->compatibilityLine();
    }

    /**
     * @return non-empty-string
     */
    public function toString(): string
    {
        return $this->text;
    }
}
