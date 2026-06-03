<?php

declare(strict_types=1);

use Velm\Views\Arch\ArchNormalizer;
use Velm\Admin\Arch\ArchSchemaBuilder;
use Velm\Admin\Arch\ListColumn;
use Velm\Admin\Tests\Support\PartnerArch;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }

    $roots = [dirname(__DIR__, 3).'/modules/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base']);
    $installer->install('partners', $roots);

    $this->env = $installer->environment($roots);
});

test('normalizes string field shorthand to field refs', function (): void {
    $arch = ArchNormalizer::normalizeList([
        'fields' => ['name', ['name' => 'active', 'widget' => 'toggle']],
    ]);

    expect($arch['fields'])->toBe([
        ['name' => 'name'],
        ['name' => 'active', 'widget' => 'toggle'],
    ]);
});

test('builds list columns from partner list arch', function (): void {
    $columns = (new ArchSchemaBuilder)->buildListColumns(PartnerArch::list($this->env), $this->env);

    expect($columns)->toHaveCount(5)
        ->and($columns[0])->toBeInstanceOf(ListColumn::class)
        ->and($columns[0]->kind)->toBe('text')
        ->and($columns[1])->toBeInstanceOf(ListColumn::class)
        ->and($columns[1]->kind)->toBe('toggle');
});

test('formats many2one list cells as display name', function (): void {
    $env = $this->env;
    $country = $env->model('res.country')->create(['name' => 'Belgium', 'code' => 'BE']);
    $partner = $env->model('res.partner')->create([
        'name' => 'Velm SA',
        'country_id' => $country->ids()[0],
        'active' => true,
    ]);

    $builder = new ArchSchemaBuilder;
    $column = collect($builder->buildListColumns(PartnerArch::list($env), $env))->firstWhere('name', 'country_id');

    expect($column)->not->toBeNull()
        ->and($column->kind)->toBe('m2o')
        ->and($builder->formatListCell($column, $partner->read()[0]['country_id'], $env))->toBe('Belgium');
});
