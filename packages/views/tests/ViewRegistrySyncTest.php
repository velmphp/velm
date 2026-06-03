<?php

declare(strict_types=1);

use Velm\Modules\ModuleInstaller;
use Velm\Views\Tests\TestCase;
use Velm\Views\ViewNotFoundException;
use Velm\Views\ViewRegistry;

uses(TestCase::class);

beforeEach(function (): void {
    config()->set('velm.addon_paths', [dirname(__DIR__, 2).'/modules/modules']);
});

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('view registry auto-syncs module data when a view is missing', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules/modules'];
    $installer = new ModuleInstaller;

    $installer->installBootstrap($roots, ['base', 'partners']);
    $env = $installer->environment($roots);

    $env->model('ir.ui.view')->search([
        ['module', '=', 'base'],
        ['name', '=', 'company.detail'],
    ])->unlink();

    expect($env->model('ir.ui.view')->search([
        ['module', '=', 'base'],
        ['name', '=', 'company.detail'],
    ])->count())->toBe(0);

    $arch = (new ViewRegistry)->arch($env, 'base', 'company.detail');

    expect($arch['view_type'])->toBe('detail')
        ->and($env->model('ir.ui.view')->search([
            ['module', '=', 'base'],
            ['name', '=', 'company.detail'],
        ])->count())->toBe(1);
});

test('view registry throws when view is missing and module is not installed', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules/modules'];
    $installer = new ModuleInstaller;

    $installer->installBootstrap($roots, ['base']);
    $env = $installer->environment($roots);

    (new ViewRegistry)->arch($env, 'partners', 'partner.detail');
})->throws(ViewNotFoundException::class);
