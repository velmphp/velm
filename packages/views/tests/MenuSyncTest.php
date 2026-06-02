<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\Tests\TestCase;
use Velm\Views\MenuRegistry;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('installing partners syncs menus into ir.ui.menu', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules/modules'];
    $installer = new ModuleInstaller;

    $installer->installBootstrap($roots, ['base']);
    $installer->install('partners', $roots);

    expect(Schema::hasTable('ir_ui_menu'))->toBeTrue();

    $env = $installer->environment($roots);
    $tree = (new MenuRegistry)->tree($env);

    expect($tree)->not->toBeEmpty();

    $partnerMenu = $env->model('ir.ui.menu')->search([
        ['module', '=', 'partners'],
        ['name', '=', 'partners'],
    ]);

    expect($partnerMenu->count())->toBe(1)
        ->and($partnerMenu->read()[0]['href'])->toBe('/velm/views/partners/partner.list');
});
