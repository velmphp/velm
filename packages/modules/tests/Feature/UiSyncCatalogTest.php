<?php

declare(strict_types=1);

use Velm\Modules\AppsCatalog;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('apps catalog marks needs_sync when views on disk differ from ir.ui.view', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;

    $installer->installBootstrap($roots, ['base', 'partners']);
    $env = $installer->environment($roots);

    expect((new AppsCatalog)->entry($roots, 'partners')['state'])->toBe('installed');

    $listView = $env->model('ir.ui.view')->search([
        ['module', '=', 'partners'],
        ['name', '=', 'partner.list'],
    ]);
    $row = $listView->read()[0];
    $arch = json_decode((string) $row['arch'], true, flags: JSON_THROW_ON_ERROR);
    $arch['title'] = 'Partners (modified in DB only)';
    $listView->write(['arch' => json_encode($arch, JSON_THROW_ON_ERROR)]);

    $entry = (new AppsCatalog)->entry($roots, 'partners');

    expect($entry)->not->toBeNull()
        ->and($entry['state'])->toBe('needs_sync')
        ->and($entry['has_schema_diff'])->toBeFalse()
        ->and($entry['has_ui_sync'])->toBeTrue()
        ->and($entry['schema_diff_summary'])->toContain('changed view');

    $installer->sync('partners', $roots);

    expect((new AppsCatalog)->entry($roots, 'partners')['state'])->toBe('installed')
        ->and($installer->hasPendingUiSync('partners', $roots))->toBeFalse();
});
