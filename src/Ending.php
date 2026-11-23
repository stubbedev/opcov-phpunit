<?php

declare(strict_types=1);

namespace OpCov\PHPUnit;

final readonly class Ending
{
    public function __construct(
        public ?string $attributedTo,
    ) {}
}
