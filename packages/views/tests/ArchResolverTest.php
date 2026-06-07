<?php

declare(strict_types=1);

use Velm\Modules\ModuleInstaller;
use Velm\Views\Arch\ArchResolver;
use Velm\Views\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('arch resolver loads partner list view from database', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'partners']);
    $env = $installer->environment($roots);

    $arch = (new ArchResolver)->resolve($env, 'partners', 'partner.list');

    expect($arch)->toHaveKey('fields')
        ->and($arch['fields'])->not->toBeEmpty();
});

test('arch resolver throws for missing view', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base']);
    $env = $installer->environment($roots);

    expect(fn () => (new ArchResolver)->resolve($env, 'base', 'missing.view'))
        ->toThrow(RuntimeException::class);
});

test('arch resolver throws when root view has empty arch', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base']);
    $env = $installer->environment($roots);

    $viewId = $env->model('ir.ui.view')->create([
        'module' => 'base',
        'name' => 'empty.arch',
        'model' => 'res.partner',
        'view_type' => 'form',
        'arch' => null,
        'priority' => 16,
        'inherit_id' => null,
        'operations' => null,
    ])->ids()[0];

    $row = $env->browse('ir.ui.view', [$viewId])->read()[0];

    expect(fn () => (new ArchResolver)->resolveRecord($env, $row))
        ->toThrow(RuntimeException::class, 'has no arch');
});
