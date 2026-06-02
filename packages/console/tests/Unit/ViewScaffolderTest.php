<?php

declare(strict_types=1);

use Velm\Console\Scaffold\ModelScaffolder;
use Velm\Console\Scaffold\ModuleScaffolder;
use Velm\Console\Scaffold\ScaffoldRegistryLoader;
use Velm\Console\Scaffold\ViewScaffoldBuilder;
use Velm\Console\Scaffold\ViewScaffolder;

test('view scaffolder creates minimal views and patches manifest', function (): void {
    $root = sys_get_temp_dir().'/velm_view_scaffold_'.uniqid('', true);
    mkdir($root, 0777, true);

    (new ModuleScaffolder)->scaffold('inventory', $root, ['base']);
    (new ModelScaffolder)->scaffold('product', 'inventory', $root.'/inventory');

    $modulePath = $root.'/inventory';
    $result = (new ViewScaffolder)->scaffold('inventory.product', 'inventory', $modulePath, false);

    expect($result['viewStem'])->toBe('product')
        ->and(is_file($modulePath.'/views/product.php'))->toBeTrue()
        ->and(file_get_contents($modulePath.'/views/product.php'))->toContain("ListView::make('product.list')")
        ->and(file_get_contents($modulePath.'/views/product.php'))->toContain("->model('inventory.product')")
        ->and(file_get_contents($modulePath.'/__velm__.php'))->toContain("->data('views/product.php')");

    unlink($modulePath.'/views/product.php');
    rmdir($modulePath.'/views');
    unlink($modulePath.'/models/product.php');
    unlink($modulePath.'/models/.gitkeep');
    rmdir($modulePath.'/models');
    unlink($modulePath.'/migrations/.gitkeep');
    rmdir($modulePath.'/migrations');
    unlink($modulePath.'/__velm__.php');
    rmdir($modulePath);
    rmdir($root);
});

test('view scaffold builder introspects partners model', function (): void {
    $modulesRoot = realpath(__DIR__.'/../../../modules/modules');
    expect($modulesRoot)->not->toBeFalse();

    $registry = (new ScaffoldRegistryLoader)->loadForModule('partners', $modulesRoot);
    $built = (new ViewScaffoldBuilder)->build($registry, 'res.partner');

    expect($built['list'])->toContain("'name'")
        ->and($built['list'])->toContain("Field::make('is_company')->toggle()");
});

test('view scaffolder resolves short model names via registry', function (): void {
    $modulesRoot = realpath(__DIR__.'/../../../modules/modules');
    expect($modulesRoot)->not->toBeFalse();

    $registry = (new ScaffoldRegistryLoader)->loadForModule('partners', $modulesRoot);
    $scaffolder = new ViewScaffolder;

    expect($scaffolder->resolveTechnical('partner', 'partners', $registry))->toBe('res.partner');
    expect($scaffolder->normalizeForViews('res.partner', 'partners', $registry))
        ->toBe(['partner', 'partner', 'res.partner']);
});
