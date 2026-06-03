<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Velm\Environment;
use Velm\Exception\AccessDeniedException;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('base install seeds admin user and security tables', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base']);

    $env = $installer->environment($roots);

    expect($env->model('res.groups')->search()->count())->toBe(3)
        ->and($env->model('res.users')->search()->count())->toBe(1)
        ->and($env->model('ir.model.access')->search()->count())->toBeGreaterThan(0);

    $adminEmail = (string) config('velm.bootstrap_admin.email', 'admin@velm.test');
    $admin = $env->model('res.users')->search([['email', '=', $adminEmail]])->read()[0];

    expect($admin['email'])->toBe($adminEmail)
        ->and($admin['group_ids'])->not->toBeEmpty();
});

test('non-superuser without partner grant cannot read partners after install', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'partners']);

    $baseEnv = $installer->environment($roots);
    $salesGroup = $baseEnv->model('res.groups')->create(['name' => 'Sales Only']);
    $baseEnv->model('res.users')->create([
        'name' => 'Limited',
        'email' => 'limited@velm.test',
        'group_ids' => $salesGroup->ids(),
    ]);

    $limitedUser = $baseEnv->model('res.users')->search([['email', '=', 'limited@velm.test']]);
    $limitedEnv = new Environment(
        $baseEnv->connection,
        $baseEnv->registry,
        uid: $limitedUser->ids()[0],
    );

    expect(fn () => $limitedEnv->model('res.partner')->search())
        ->toThrow(AccessDeniedException::class);
});
