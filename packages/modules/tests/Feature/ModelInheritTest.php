<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\Partners\Models\Partner;
use Velm\Modules\Tests\Support\PartnerChainedExtension;
use Velm\Modules\Tests\Support\PartnerExtension;
use Velm\Modules\Tests\Support\PartnerIndependentExtension;
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

    expect($partner->read()[0]['ref'])->toBe('ACME-001')
        ->and($partner->read()[0]['display_name'])->toBe('Acme (ACME-001)');
});

test('independent extensions stack in module load order via static::super()', function (): void {
    $roots = [
        dirname(__DIR__, 2).'/modules',
        dirname(__DIR__, 2).'/tests/fixtures',
    ];
    $installer = new ModuleInstaller;

    $installer->installBootstrap($roots, ['base']);
    $installer->install('partners', $roots);
    $installer->install('partners_ext', $roots);
    $installer->install('partners_ext_independent', $roots);

    expect(Schema::hasColumn('res_partner', 'ref'))->toBeTrue()
        ->and(Schema::hasColumn('res_partner', 'independent_note'))->toBeTrue();

    $env = $installer->environment($roots);
    $partner = $env->model('res.partner')->create([
        'name' => 'Velm Labs',
        'ref' => 'VL-001',
        'independent_note' => 'vip',
    ]);

    expect($partner->read()[0]['display_name'])->toBe('Velm Labs (VL-001) {vip}')
        ->and($env->registry->modelClass('res.partner'))->toBe(PartnerIndependentExtension::class)
        ->and($env->registry->extensionChainFor('res.partner'))->toBe([
            Partner::class,
            PartnerExtension::class,
            PartnerIndependentExtension::class,
        ]);
});

test('multiple extension modules compose display_name through super chain', function (): void {
    $roots = [
        dirname(__DIR__, 2).'/modules',
        dirname(__DIR__, 2).'/tests/fixtures',
    ];
    $installer = new ModuleInstaller;

    $installer->installBootstrap($roots, ['base']);
    $installer->install('partners', $roots);
    $installer->install('partners_ext', $roots);
    $installer->install('partners_ext_chained', $roots);

    expect(Schema::hasColumn('res_partner', 'ref'))->toBeTrue()
        ->and(Schema::hasColumn('res_partner', 'chain_tag'))->toBeTrue();

    $env = $installer->environment($roots);
    $partner = $env->model('res.partner')->create([
        'name' => 'Velm Labs',
        'ref' => 'VL-001',
        'chain_tag' => 'gold',
    ]);

    expect($partner->read()[0]['display_name'])->toBe('Velm Labs (VL-001) #gold')
        ->and($env->registry->modelClass('res.partner'))->toBe(PartnerChainedExtension::class)
        ->and($env->registry->extensionsFor('res.partner'))->toBe([
            PartnerExtension::class,
            PartnerChainedExtension::class,
        ]);
});

