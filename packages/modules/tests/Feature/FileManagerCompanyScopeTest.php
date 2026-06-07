<?php

declare(strict_types=1);

use Velm\Modules\FileManager\FileManagerCompanyScope;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('backfill assigns default company to unscoped attachments and folders', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('file_manager', $roots);

    $env = $installer->environment($roots);
    $companyId = FileManagerCompanyScope::defaultCompanyId($env);
    expect($companyId)->not->toBeNull();

    $env->withAclBypass(function () use ($env): void {
        $env->model('ir.attachment')->create([
            'name' => 'orphan.txt',
            'type' => 'binary',
            'company_id' => null,
        ]);
        $env->model('res.attachment.folder')->create([
            'name' => 'Orphan folder',
            'company_id' => null,
        ]);
    });

    $scoped = $env->withContext(['company_id' => $companyId]);

    expect($scoped->model('ir.attachment')->search([['name', '=', 'orphan.txt']])->count())->toBe(0)
        ->and($scoped->model('res.attachment.folder')->search([['name', '=', 'Orphan folder']])->count())->toBe(0);

    FileManagerCompanyScope::backfillOrphans($env);

    expect($scoped->model('ir.attachment')->search([['name', '=', 'orphan.txt']])->count())->toBe(1)
        ->and($scoped->model('res.attachment.folder')->search([['name', '=', 'Orphan folder']])->count())->toBe(1);
});

test('file_manager sync hook backfills unscoped library rows', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('file_manager', $roots);

    $env = $installer->environment($roots);
    $companyId = FileManagerCompanyScope::defaultCompanyId($env);
    expect($companyId)->not->toBeNull();

    $env->withAclBypass(function () use ($env): void {
        $env->model('ir.attachment')->create([
            'name' => 'sync-orphan.txt',
            'type' => 'binary',
            'company_id' => null,
        ]);
    });

    $scoped = $env->withContext(['company_id' => $companyId]);

    expect($scoped->model('ir.attachment')->search([['name', '=', 'sync-orphan.txt']])->count())->toBe(0);

    $installer->sync('file_manager', $roots);

    expect($scoped->model('ir.attachment')->search([['name', '=', 'sync-orphan.txt']])->count())->toBe(1);
});

test('envForCreate stamps company when request has no active company', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('file_manager', $roots);

    $env = $installer->environment($roots);
    $companyId = FileManagerCompanyScope::defaultCompanyId($env);
    expect($companyId)->not->toBeNull();

    $folder = FileManagerCompanyScope::envForCreate($env)
        ->model('res.attachment.folder')
        ->create(['name' => 'Stamped folder']);

    expect($folder->read(['company_id'])[0]['company_id'])->toBe($companyId);
});

test('stampCompanyId falls back to default company when active company is unset', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('file_manager', $roots);

    $env = $installer->environment($roots);
    $defaultId = FileManagerCompanyScope::defaultCompanyId($env);

    expect(FileManagerCompanyScope::stampCompanyId($env->withContext(['company_id' => null])))
        ->toBe($defaultId);
});

test('backfillOrphans no-ops when no default company exists', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('file_manager', $roots);

    $env = $installer->environment($roots);

    $env->withAclBypass(function () use ($env): void {
        $env->model('res.company')->search([])->unlink();
    });

    FileManagerCompanyScope::backfillOrphans($env);

    expect(true)->toBeTrue();
});
