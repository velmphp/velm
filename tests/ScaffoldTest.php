<?php

declare(strict_types=1);

use Velm\Velm;

test('version constant exists', function (): void {
    expect(Velm::VERSION)->toBe('0.1.0-dev');
});
