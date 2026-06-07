<?php

declare(strict_types=1);

use Velm\Console\Scaffold\AutogenViewEnsurer;
use Velm\Console\Scaffold\ScaffoldRegistryLoader;
use Velm\Modules\Manifest;
use Velm\Modules\ModuleSpec;
use Velm\Modules\Partners\Models\Partner;
use Velm\Schema\SchemaDiff;

test('autogen view ensurer detects models affected by new tables', function (): void {
    $modulesRoot = realpath(__DIR__.'/../../../modules/modules');
    expect($modulesRoot)->not->toBeFalse();

    $spec = new ModuleSpec(
        name: 'partners',
        version: [1, 0, 0],
        depends: ['base'],
        path: $modulesRoot.'/partners',
        models: [Partner::class],
    );

    $registry = (new ScaffoldRegistryLoader)->loadForModule('partners', $modulesRoot);
    $diff = new SchemaDiff;
    $diff->newTables[] = ['res_partner', Partner::class];

    $models = (new AutogenViewEnsurer)->modelsAffectedByDiff($spec, $registry, $diff);

    expect($models)->toContain('res.partner');
});

test('autogen view ensurer detects existing list view in partners module', function (): void {
    $modulesRoot = realpath(__DIR__.'/../../../modules/modules');
    $spec = new ModuleSpec(
        name: 'partners',
        version: [1, 0, 0],
        depends: ['base'],
        path: $modulesRoot.'/partners',
    );

    expect((new AutogenViewEnsurer)->modelHasListView($spec, 'res.partner'))->toBeTrue();
});

test('autogen view ensurer ensureViews skips models that already have list views', function (): void {
    $modulesRoot = realpath(__DIR__.'/../../../modules/modules');
    $spec = new ModuleSpec(
        name: 'partners',
        version: [1, 0, 0],
        depends: ['base'],
        path: $modulesRoot.'/partners',
        models: [Partner::class],
    );
    $registry = (new ScaffoldRegistryLoader)->loadForModule('partners', $modulesRoot);

    $created = (new AutogenViewEnsurer)->ensureViews($spec, ['res.partner'], $registry);

    expect($created)->toBe([]);
});
