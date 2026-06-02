<?php

declare(strict_types=1);

use Velm\Console\Scaffold\ModuleScaffolder;

test('module scaffolder creates manifest and directories', function (): void {
    $root = sys_get_temp_dir().'/velm_scaffold_'.uniqid('', true);
    mkdir($root, 0777, true);

    $path = (new ModuleScaffolder)->scaffold('inventory', $root, ['base', 'partners']);

    expect(is_dir($path))->toBeTrue()
        ->and(is_file($path.'/__velm__.php'))->toBeTrue()
        ->and(file_get_contents($path.'/__velm__.php'))->toContain("Manifest::make('inventory')")
        ->and(file_get_contents($path.'/__velm__.php'))->toContain("->depends('base', 'partners')");

    array_map(unlink(...), [
        $path.'/models/.gitkeep',
        $path.'/migrations/.gitkeep',
        $path.'/__velm__.php',
    ]);
    rmdir($path.'/models');
    rmdir($path.'/migrations');
    rmdir($path);
    rmdir($root);
});

test('module scaffolder rejects invalid names', function (): void {
    (new ModuleScaffolder)->scaffold('Bad-Name', sys_get_temp_dir());
})->throws(\InvalidArgumentException::class);
