<?php

declare(strict_types=1);

use Velm\Modules\ModuleInstaller;
use Velm\Modules\Tests\TestCase;
use Velm\Views\Sync\UiSyncDiffer;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('ui sync differ detects changed views', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'partners']);
    $env = $installer->environment($roots);
    $spec = $installer->discover($roots)['partners'];

    $differ = new UiSyncDiffer;
    expect($differ->hasPending($spec, $env))->toBeFalse();

    $listView = $env->model('ir.ui.view')->search([
        ['module', '=', 'partners'],
        ['name', '=', 'partner.list'],
    ]);
    $row = $listView->read()[0];
    $arch = json_decode((string) $row['arch'], true, flags: JSON_THROW_ON_ERROR);
    $arch['title'] = 'Changed title';
    $listView->write(['arch' => json_encode($arch, JSON_THROW_ON_ERROR)]);

    $diff = $differ->diff($spec, $env);

    expect($diff->hasChanges())->toBeTrue()
        ->and($diff->changedViews)->toContain('partner.list');
});

test('ui sync differ hasPending matches apps catalog ui drift', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'partners']);
    $env = $installer->environment($roots);
    $spec = $installer->discover($roots)['partners'];

    expect((new UiSyncDiffer)->hasPending($spec, $env))->toBeFalse()
        ->and($installer->hasPendingUiSync('partners', $roots))->toBeFalse();
});
