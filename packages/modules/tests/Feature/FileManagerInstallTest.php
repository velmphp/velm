<?php

declare(strict_types=1);

use Velm\Modules\ModuleInstaller;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

test('file_manager install extends ir.attachment and grants folder access', function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }

    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('file_manager', $roots);

    $env = $installer->environment($roots);

    expect($env->registry->has('res.attachment.folder'))->toBeTrue()
        ->and($env->registry->fieldSet('ir.attachment'))->toHaveKeys(['folder_id', 'company_id']);

    $admin = $env->model('res.groups')->search([['name', '=', 'Admin']], limit: 1);
    $access = $env->model('ir.model.access')->search([
        ['model', '=', 'res.attachment.folder'],
        ['group_id', '=', $admin->ids()[0]],
    ]);

    expect($access->count())->toBe(1)
        ->and($access->read(['perm_read', 'perm_write'])[0]['perm_write'])->toBeTrue();
});
