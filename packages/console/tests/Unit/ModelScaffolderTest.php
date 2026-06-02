<?php

declare(strict_types=1);

use Velm\Console\Scaffold\ModelScaffolder;
use Velm\Console\Scaffold\ModuleScaffolder;

test('model scaffolder creates class and updates manifest', function (): void {
    $root = sys_get_temp_dir().'/velm_model_scaffold_'.uniqid('', true);
    mkdir($root, 0777, true);

    (new ModuleScaffolder)->scaffold('inventory', $root, ['base']);
    $modulePath = $root.'/inventory';

    $result = (new ModelScaffolder)->scaffold('product', 'inventory', $modulePath);

    expect($result['technical'])->toBe('inventory.product')
        ->and($result['class'])->toBe('Product')
        ->and(is_file($modulePath.'/models/product.php'))->toBeTrue()
        ->and(file_get_contents($modulePath.'/models/product.php'))->toContain("protected static ?string \$name = 'inventory.product';")
        ->and(file_get_contents($modulePath.'/__velm__.php'))->toContain('use Addons\\Inventory\\Models\\Product;')
        ->and(file_get_contents($modulePath.'/__velm__.php'))->toContain('Product::class');

    unlink($modulePath.'/models/product.php');
    unlink($modulePath.'/models/.gitkeep');
    rmdir($modulePath.'/models');
    unlink($modulePath.'/migrations/.gitkeep');
    rmdir($modulePath.'/migrations');
    unlink($modulePath.'/__velm__.php');
    rmdir($modulePath);
    rmdir($root);
});

test('model scaffolder refuses overwrite without force', function (): void {
    $root = sys_get_temp_dir().'/velm_model_scaffold_'.uniqid('', true);
    mkdir($root, 0777, true);

    (new ModuleScaffolder)->scaffold('demo', $root);
    $modulePath = $root.'/demo';
    $scaffolder = new ModelScaffolder;

    $scaffolder->scaffold('item', 'demo', $modulePath);
    $scaffolder->scaffold('item', 'demo', $modulePath);
})->throws(\RuntimeException::class);

test('model scaffolder accepts technical model names', function (): void {
    $root = sys_get_temp_dir().'/velm_model_scaffold_'.uniqid('', true);
    mkdir($root, 0777, true);

    (new ModuleScaffolder)->scaffold('sales', $root);
    $modulePath = $root.'/sales';

    $result = (new ModelScaffolder)->scaffold('sales.order_line', 'sales', $modulePath);

    expect($result['technical'])->toBe('sales.order_line')
        ->and($result['class'])->toBe('OrderLine');

    unlink($modulePath.'/models/order_line.php');
    unlink($modulePath.'/models/.gitkeep');
    rmdir($modulePath.'/models');
    unlink($modulePath.'/migrations/.gitkeep');
    rmdir($modulePath.'/migrations');
    unlink($modulePath.'/__velm__.php');
    rmdir($modulePath);
    rmdir($root);
});
