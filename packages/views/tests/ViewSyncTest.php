<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\Tests\TestCase;
use Velm\Views\ViewRegistry;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('installing partners syncs views into ir.ui.view', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules/modules'];
    $installer = new ModuleInstaller;

    $installer->installBootstrap($roots, ['base']);
    $installer->install('partners', $roots);

    expect(Schema::hasTable('ir_ui_view'))->toBeTrue();

    $env = $installer->environment($roots);
    $registry = new ViewRegistry;

    $list = $registry->arch($env, 'partners', 'partner.list');

    expect($list['view_type'])->toBe('list')
        ->and($list['model'])->toBe('res.partner')
        ->and($list['fields'])->not->toBeEmpty()
        ->and($list['detail_view'])->toBe('partner.detail');

    $detail = $registry->arch($env, 'partners', 'partner.detail');

    expect($detail['view_type'])->toBe('detail')
        ->and($detail['sections'])->not->toBeEmpty();
});

test('module sync reloads view data without reinstall', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules/modules'];
    $installer = new ModuleInstaller;

    $installer->installBootstrap($roots, ['base', 'admin', 'partners']);
    $installer->sync('admin', $roots);
    $installer->sync('partners', $roots);

    $env = $installer->environment($roots);

    expect($env->model('ir.ui.view')->search([
        ['module', '=', 'partners'],
    ])->count())->toBeGreaterThan(0);
});
