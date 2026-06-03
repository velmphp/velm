<?php

declare(strict_types=1);

use Velm\Console\Scaffold\AutogenViewEnsurer;
use Velm\Console\Scaffold\MenuScaffolder;
use Velm\Console\Scaffold\ModelScaffolder;
use Velm\Console\Scaffold\ModuleScaffolder;
use Velm\Console\Scaffold\ScaffoldRegistryLoader;
use Velm\Console\Scaffold\ViewScaffolder;

test('menu scaffolder creates menu file and patches manifest', function (): void {
    $root = sys_get_temp_dir().'/velm_menu_scaffold_'.uniqid('', true);
    mkdir($root, 0777, true);

    (new ModuleScaffolder)->scaffold('inventory', $root, ['base']);
    (new ModelScaffolder)->scaffold('product', 'inventory', $root.'/inventory');
    (new ViewScaffolder)->scaffold('inventory.product', 'inventory', $root.'/inventory', false);

    $modulePath = $root.'/inventory';
    $result = (new MenuScaffolder)->scaffold(
        'inventory',
        $modulePath,
        'product.list',
    );

    expect($result['view'])->toBe('product.list')
        ->and(is_file($modulePath.'/views/menu.php'))->toBeTrue()
        ->and(file_get_contents($modulePath.'/views/menu.php'))->toContain("->view('product.list')")
        ->and(file_get_contents($modulePath.'/__velm__.php'))->toContain("'views/menu.php'");

    unlink($modulePath.'/views/menu.php');
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

test('menu scaffolder append adds item to existing menu file', function (): void {
    $root = sys_get_temp_dir().'/velm_menu_scaffold_'.uniqid('', true);
    mkdir($root, 0777, true);

    (new ModuleScaffolder)->scaffold('sales', $root);
    $modulePath = $root.'/sales';
    $scaffolder = new MenuScaffolder;

    $scaffolder->scaffold('sales', $modulePath, 'order.list');
    $scaffolder->scaffold('sales', $modulePath, 'line.list', append: true);

    $contents = file_get_contents($modulePath.'/views/menu.php');

    expect($contents)->toContain("->view('order.list')")
        ->and($contents)->toContain("->view('line.list')");

    unlink($modulePath.'/views/menu.php');
    rmdir($modulePath.'/views');
    unlink($modulePath.'/models/.gitkeep');
    rmdir($modulePath.'/models');
    unlink($modulePath.'/migrations/.gitkeep');
    rmdir($modulePath.'/migrations');
    unlink($modulePath.'/__velm__.php');
    rmdir($modulePath);
    rmdir($root);
});

test('autogen view ensurer detects models from schema diff tables', function (): void {
    $modulesRoot = realpath(__DIR__.'/../../../modules/modules');
    expect($modulesRoot)->not->toBeFalse();

    $registry = (new ScaffoldRegistryLoader)->loadForModule('partners', $modulesRoot);
    $specs = (new \Velm\Modules\ModuleDiscovery)->discover([$modulesRoot]);
    $spec = $specs['partners'];

    $diff = new \Velm\Schema\SchemaDiff;
    $diff->newTables = [['res_partner', \Velm\Modules\Partners\Models\Partner::class]];

    $affected = (new AutogenViewEnsurer)->modelsAffectedByDiff($spec, $registry, $diff);

    expect($affected)->toContain('res.partner');
});
