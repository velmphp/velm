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

test('autogen view ensurer detects models affected by new columns', function (): void {
    $modulesRoot = realpath(__DIR__.'/../../../modules/modules');
    $spec = new ModuleSpec(
        name: 'partners',
        version: [1, 0, 0],
        depends: ['base'],
        path: $modulesRoot.'/partners',
        models: [Partner::class],
    );
    $registry = (new ScaffoldRegistryLoader)->loadForModule('partners', $modulesRoot);
    $diff = new SchemaDiff;
    $diff->newColumns[] = ['res_partner', 'ref'];

    expect((new AutogenViewEnsurer)->modelsAffectedByDiff($spec, $registry, $diff))->toContain('res.partner');
});

test('autogen view ensurer returns empty when diff has no tables', function (): void {
    $modulesRoot = realpath(__DIR__.'/../../../modules/modules');
    $spec = new ModuleSpec(
        name: 'partners',
        version: [1, 0, 0],
        depends: ['base'],
        path: $modulesRoot.'/partners',
        models: [Partner::class],
    );
    $registry = (new ScaffoldRegistryLoader)->loadForModule('partners', $modulesRoot);

    expect((new AutogenViewEnsurer)->modelsAffectedByDiff($spec, $registry, new SchemaDiff))->toBe([]);
});

test('autogen view ensurer modelHasListView finds embedded model reference in data file', function (): void {
    $dir = sys_get_temp_dir().'/velm-autogen-'.uniqid('', true);
    mkdir($dir.'/views', 0777, true);
    file_put_contents($dir.'/extra.php', "<?php\nreturn ['list' => fn () => ListView::make()->model('demo.widget')];");

    $spec = new ModuleSpec(
        name: 'demo',
        version: [0, 1, 0],
        depends: [],
        path: $dir,
        data: ['extra.php'],
    );

    expect((new AutogenViewEnsurer)->modelHasListView($spec, 'demo.widget'))->toBeTrue();

    @unlink($dir.'/extra.php');
    @rmdir($dir.'/views');
    @rmdir($dir);
});

test('autogen view ensurer ensureViews scaffolds missing list views', function (): void {
    $dir = sys_get_temp_dir().'/velm-autogen-views-'.uniqid('', true);
    mkdir($dir.'/views', 0777, true);
    file_put_contents($dir.'/__velm__.php', "<?php\nuse Velm\\Modules\\Manifest;\nreturn Manifest::make('partners')->version(1, 0, 0)->depends('base');\n");
    $modulesRoot = realpath(__DIR__.'/../../../modules/modules');
    $spec = new ModuleSpec(
        name: 'partners',
        version: [1, 0, 0],
        depends: ['base'],
        path: $dir,
        models: [Partner::class],
    );
    $registry = (new ScaffoldRegistryLoader)->loadForModule('partners', $modulesRoot);

    $created = (new AutogenViewEnsurer)->ensureViews($spec, ['res.partner'], $registry);

    expect($created)->toHaveCount(1)
        ->and(is_file($created[0]))->toBeTrue();

    @unlink($created[0]);
    @unlink($dir.'/__velm__.php');
    @rmdir($dir.'/views');
    @rmdir($dir);
});
