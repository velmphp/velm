<?php

declare(strict_types=1);

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

function partnerMethodRoots(): array
{
    return [
        dirname(__DIR__, 2).'/modules',
        dirname(__DIR__, 2).'/tests/fixtures',
    ];
}

test('base partner badge without extension modules', function (): void {
    $installer = new ModuleInstaller;
    $roots = partnerMethodRoots();

    $installer->installBootstrap($roots, ['base']);
    $installer->install('partners', $roots);

    $env = $installer->environment($roots);
    $partner = $env->model('res.partner')->create(['name' => 'Acme']);

    expect($partner->badge())->toBe('Acme')
        ->and($env->registry->modelClass('res.partner'))->toBe(Partner::class);
});

test('recordset instance methods chain via static::super() through skipped middle extension', function (): void {
    $installer = new ModuleInstaller;
    $roots = partnerMethodRoots();

    $installer->installBootstrap($roots, ['base']);
    $installer->install('partners', $roots);
    $installer->install('partners_ext', $roots);
    $installer->install('partners_ext_chained', $roots);

    $env = $installer->environment($roots);
    $partner = $env->model('res.partner')->create([
        'name' => 'Velm Labs',
        'ref' => 'VL-001',
        'chain_tag' => 'gold',
    ]);

    expect($partner->badge())->toBe('Velm Labs · VL-001')
        ->and(PartnerExtension::isRecordMethod('badge'))->toBeFalse()
        ->and(PartnerChainedExtension::isRecordMethod('badge'))->toBeTrue();
});

test('stacked static display_name and instance badge work together after full install', function (): void {
    $installer = new ModuleInstaller;
    $roots = partnerMethodRoots();

    $installer->installBootstrap($roots, ['base']);
    $installer->install('partners', $roots);
    $installer->install('partners_ext', $roots);
    $installer->install('partners_ext_independent', $roots);
    $installer->install('partners_ext_chained', $roots);

    $env = $installer->environment($roots);
    $partner = $env->model('res.partner')->create([
        'name' => 'Velm Labs',
        'ref' => 'VL-001',
        'independent_note' => 'vip',
        'chain_tag' => 'gold',
    ]);

    expect($partner->read()[0]['display_name'])->toBe('Velm Labs (VL-001) #gold {vip}')
        ->and($partner->badge())->toBe('Velm Labs · VL-001')
        ->and($env->registry->extensionChainFor('res.partner'))->toBe([
            Partner::class,
            PartnerExtension::class,
            PartnerChainedExtension::class,
            PartnerIndependentExtension::class,
        ]);
});

test('independent extension does not shadow badge when it does not implement badge', function (): void {
    $installer = new ModuleInstaller;
    $roots = partnerMethodRoots();

    $installer->installBootstrap($roots, ['base']);
    $installer->install('partners', $roots);
    $installer->install('partners_ext_independent', $roots);

    $env = $installer->environment($roots);
    $partner = $env->model('res.partner')->create([
        'name' => 'Solo',
        'independent_note' => 'note',
    ]);

    expect($partner->badge())->toBe('Solo')
        ->and($partner->read()[0]['display_name'])->toBe('Solo {note}');
});
