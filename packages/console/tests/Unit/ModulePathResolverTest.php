<?php

declare(strict_types=1);

use Velm\Console\Scaffold\ModulePathResolver;

test('module path resolver finds bundled partners module', function (): void {
    $path = ModulePathResolver::findModulePath('partners');

    expect($path)->toEndWith('/partners')
        ->and(is_file($path.'/__velm__.php'))->toBeTrue();
});

test('module path resolver rejects invalid module names', function (): void {
    ModulePathResolver::findModulePath('Bad-Name');
})->throws(InvalidArgumentException::class);

test('module path resolver uses explicit addon root', function (): void {
    $root = dirname(__DIR__, 3).'/modules/modules';
    $path = ModulePathResolver::findModulePath('partners', $root);

    expect($path)->toBe($root.'/partners');
});

test('module path resolver studly name and bundled detection', function (): void {
    $path = ModulePathResolver::findModulePath('partners');

    expect(ModulePathResolver::studlyModuleName('partners'))->toBe('Partners')
        ->and(ModulePathResolver::isBundledModulePath($path))->toBeTrue()
        ->and(ModulePathResolver::modelsNamespace($path, 'partners'))->toContain('Partners');
});

test('module path resolver resolve addon root prefers explicit path', function (): void {
    $explicit = '/tmp/custom-addons';

    expect(ModulePathResolver::resolveAddonRoot($explicit))->toBe($explicit);
});
