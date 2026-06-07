<?php

declare(strict_types=1);

use Velm\Console\Scaffold\ScaffoldRegistryLoader;

test('scaffold registry loader loads partners module registry', function (): void {
    $root = dirname(__DIR__, 3).'/modules/modules';
    $registry = (new ScaffoldRegistryLoader)->loadForModule('partners', $root);

    expect($registry->has('res.partner'))->toBeTrue()
        ->and($registry->hasFieldSet('res.partner'))->toBeTrue();
});

test('scaffold registry loader infers module from technical model name', function (): void {
    $root = dirname(__DIR__, 3).'/modules/modules';
    $loader = new ScaffoldRegistryLoader;

    expect($loader->inferModuleForModel('res.partner', $root))->toBe('partners')
        ->and($loader->inferModuleForModel('partner', $root))->toBe('partners');
});

test('scaffold registry loader throws for unknown module', function (): void {
    (new ScaffoldRegistryLoader)->loadForModule('nonexistent_module_xyz', dirname(__DIR__, 3).'/modules/modules');
})->throws(RuntimeException::class);
