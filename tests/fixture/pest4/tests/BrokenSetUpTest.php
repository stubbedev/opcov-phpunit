<?php

declare(strict_types=1);

use Fixture\Label;

beforeEach(function (): void {
    Label::prepare();

    throw new RuntimeException('setUp breaks on purpose');
});

test('never runs', function (): void {
    $this->fail('setUp throws first');
});
