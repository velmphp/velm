<?php

declare(strict_types=1);

use Velm\Database\PdoConnection;
use Velm\Environment;
use Velm\Modules\Mail\MailInstallHooks;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\Tests\Support\ModuleRoots;
use Velm\Modules\Tests\TestCase;
use Velm\Registry;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('mail install hooks grant access for mail models', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('mail', $roots);

    $env = $installer->environment($roots);

    MailInstallHooks::install($env);

    $adminGroupId = $env->model('res.groups')->search([['name', '=', 'Admin']], limit: 1)->ids()[0];
    $access = $env->model('ir.model.access')->search([
        ['model', '=', 'mail.message'],
        ['group_id', '=', $adminGroupId],
    ]);

    expect($access->count())->toBe(1)
        ->and($access->read(['perm_read'])[0]['perm_read'])->toBeTrue();
});

test('mail install hooks updates existing access rows idempotently', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('mail', $roots);

    $env = $installer->environment($roots);
    $adminGroupId = $env->model('res.groups')->search([['name', '=', 'Admin']], limit: 1)->ids()[0];

    MailInstallHooks::install($env);
    MailInstallHooks::install($env);

    expect($env->model('ir.model.access')->search([
        ['model', '=', 'mail.follower'],
        ['group_id', '=', $adminGroupId],
    ])->count())->toBe(1);
});

test('mail install hooks no-ops without access model', function (): void {
    $env = new Environment(PdoConnection::sqliteMemory(), new Registry);

    MailInstallHooks::install($env);

    expect(true)->toBeTrue();
});
