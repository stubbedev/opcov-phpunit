<?php

declare(strict_types=1);

use Fixture\Label;

test('formats a named label', function (): void {
    expect(Label::format('Ada', 'Oslo'))->toBe('ADA, Oslo');
});

test('refuses a label without city', function (): void {
    Label::format(null, '');
})->throws(InvalidArgumentException::class);
