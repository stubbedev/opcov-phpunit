<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Branch;

use InvalidArgumentException;
use OpCov\PHPUnit\Json;

enum Id: string
{
    case Outcome = 'o';
    case Loop = 'l';

    /**
     * @return int<0, max>
     */
    public function number(string $id, string $what): int
    {
        if (preg_match('/\A' . $this->value . '(0|[1-9]\d{0,8})\z/', $id) !== 1) {
            throw new InvalidArgumentException(sprintf('%s "%s" is not %s id', $what, $id, $this->noun()));
        }

        return Json::nonNegativeInt((int) substr($id, offset: 1), $what);
    }

    private function noun(): string
    {
        return match ($this) {
            self::Outcome => 'an outcome',
            self::Loop => 'a loop',
        };
    }
}
