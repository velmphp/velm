<?php

declare(strict_types=1);

use Velm\Modules\ModuleVersion;

test('module version compare and upgrade detection', function (): void {
    expect(ModuleVersion::compare([0, 1, 0], [0, 2, 0]))->toBe(-1)
        ->and(ModuleVersion::needsUpgrade([0, 1, 0], [0, 2, 0]))->toBeTrue()
        ->and(ModuleVersion::needsUpgrade([0, 2, 0], [0, 2, 0]))->toBeFalse();
});

test('migration filename parsing', function (): void {
    expect(ModuleVersion::parseMigrationFilename('0_1_0_to_0_2_0'))
        ->toBe([[0, 1, 0], [0, 2, 0]])
        ->and(ModuleVersion::parseMigrationFilename('bad'))->toBeNull();
});
