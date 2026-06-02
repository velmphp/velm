<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('installing a module with model inherit adds columns and exposes merged fields', function (): void {
    $roots = [
        dirname(__DIR__, 2).'/modules',
        dirname(__DIR__, 2).'/tests/fixtures',
    ];
    $installer = new ModuleInstaller;

    $installer->installBootstrap($roots, ['base']);
    $installer->install('partners', $roots);
    $installer->install('partners_ext', $roots);

    expect(Schema::hasColumn('res_partner', 'ref'))->toBeTrue();

    $env = $installer->environment($roots);
    $partner = $env->model('res.partner')->create([
        'name' => 'Acme',
        'ref' => 'ACME-001',
    ]);

    expect($partner->read()[0]['ref'])->toBe('ACME-001');
});
