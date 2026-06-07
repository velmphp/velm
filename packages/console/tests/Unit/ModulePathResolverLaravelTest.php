<?php

declare(strict_types=1);

use Velm\Console\Scaffold\ModulePathResolver;
use Velm\Console\Tests\ConsoleTestCase;

uses(ConsoleTestCase::class);

test('module path resolver resolve addon root uses laravel base path when available', function (): void {
    $root = ModulePathResolver::resolveAddonRoot(null);

    expect($root)->not->toBe('');
});
