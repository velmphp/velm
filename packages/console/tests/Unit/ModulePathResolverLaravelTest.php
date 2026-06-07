<?php

declare(strict_types=1);

use Velm\Console\Scaffold\ModulePathResolver;
use Velm\Console\Tests\ConsoleTestCase;

uses(ConsoleTestCase::class);

test('module path resolver resolve addon root uses laravel base path when available', function (): void {
    $root = ModulePathResolver::resolveAddonRoot(null);

    expect($root)->toBe(base_path('addons'))
        ->and(ModulePathResolver::moduleSearchRoots())->not->toBeEmpty();
});

test('module path resolver inferModuleFromCwd detects module from nested working directory', function (): void {
    $modulesRoot = dirname(__DIR__, 3).'/modules/modules';
    $cwd = $modulesRoot.'/partners/views';

    expect(ModulePathResolver::inferModuleFromCwd($cwd))->toBe('partners');
});

test('module path resolver inferModuleFromCwd rejects empty cwd override', function (): void {
    expect(ModulePathResolver::inferModuleFromCwd(''))->toBeNull();
});
