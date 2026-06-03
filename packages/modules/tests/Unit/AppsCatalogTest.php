<?php

declare(strict_types=1);

use Velm\Modules\AppsCatalog;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

test('apps catalog includes manifest metadata and dependency state', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $catalog = (new AppsCatalog)->entries($roots);

    expect($catalog)->not->toBeEmpty();

    $base = collect($catalog)->firstWhere('name', 'base');

    expect($base)->not->toBeNull()
        ->and($base)->toHaveKeys([
            'display_name', 'summary', 'category', 'available_version',
            'state', 'depends', 'deps_missing', 'deps_unknown',
            'has_schema_diff', 'version_upgrade',
        ])
        ->and($base['state'])->toBeIn(['installed', 'to_upgrade', 'needs_sync', 'uninstalled']);
});

test('module installer catalog delegates to apps catalog', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $row = (new ModuleInstaller)->catalog($roots)[0];

    expect($row)->toHaveKey('display_name')
        ->and($row)->not->toHaveKey('version');
});
