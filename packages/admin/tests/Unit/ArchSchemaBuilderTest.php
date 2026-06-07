<?php

declare(strict_types=1);

use Velm\Views\Arch\ArchNormalizer;
use Velm\Admin\Arch\ArchSchemaBuilder;
use Velm\Admin\Arch\ListColumn;
use Velm\Admin\Tests\Support\PartnerArch;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\Tests\Support\PartnerExtension;
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

test('arch schema builder maps relation widgets and formats grouped labels', function (): void {
    $roots = [dirname(__DIR__, 3).'/modules/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $builder = new ArchSchemaBuilder;

    $workflowColumns = $builder->buildListColumns([
        'model' => 'workflow.definition',
        'fields' => [
            ['name' => 'name'],
            ['name' => 'group_ids', 'widget' => 'files'],
        ],
    ], $env);
    $groupColumn = collect($workflowColumns)->firstWhere('name', 'group_ids');

    expect($groupColumn)->not->toBeNull()
        ->and($groupColumn->kind)->toBe('files');

    $countryColumns = $builder->buildListColumns([
        'model' => 'res.country',
        'fields' => [
            ['name' => 'name'],
            ['name' => 'partner_ids'],
        ],
    ], $env);
    $partnerColumn = collect($countryColumns)->firstWhere('name', 'partner_ids');

    expect($partnerColumn)->not->toBeNull()
        ->and($partnerColumn->kind)->toBe('o2m')
        ->and($builder->formatGroupLabel('res.partner', 'active', null, $env))->toBe('—')
        ->and($builder->formatGroupLabel('res.partner', 'active', true, $env))->toBe('Yes')
        ->and($builder->formatGroupLabel('res.partner', 'country_id', false, $env))->toBe('—');

    $country = $env->model('res.country')->create(['name' => 'Schema NL', 'code' => 'NL']);
    $partner = $env->model('res.partner')->create([
        'name' => 'Schema Partner',
        'country_id' => $country->ids()[0],
    ]);
    $m2oColumn = new ListColumn('country_id', 'm2o', 'res.country');

    expect($builder->formatListCell($m2oColumn, $partner->read()[0]['country_id'], $env))->toBe('Schema NL')
        ->and($builder->formatListCell(new ListColumn('active', 'toggle'), true, $env))->toBe('Yes')
        ->and($builder->formatListCell(new ListColumn('tags', 'text'), ['a', 'b'], $env))->toBe('a, b')
        ->and($builder->formatListCell(new ListColumn('active', 'text'), false, $env))->toBe('No')
        ->and($builder->formatListCell(new ListColumn('country_id', 'file', 'res.country'), $country->ids()[0], $env))->toBe('Schema NL');
});

test('arch schema builder handles file widgets and plain group labels', function (): void {
    $env = $this->env;
    $builder = new ArchSchemaBuilder;

    $fileColumn = $builder->buildListColumns([
        'model' => 'res.partner',
        'fields' => [['name' => 'country_id', 'widget' => 'file']],
    ], $env)[0];

    expect($fileColumn->kind)->toBe('file');

    $roots = [dirname(__DIR__, 3).'/modules/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base']);
    $installer->install('workflow', $roots);
    $workflowEnv = $installer->environment($roots);

    $m2mColumn = $builder->buildListColumns([
        'model' => 'workflow.definition',
        'fields' => [['name' => 'group_ids']],
    ], $workflowEnv)[0];

    expect($m2mColumn->kind)->toBe('m2m')
        ->and($builder->formatGroupLabel('res.partner', 'name', 'Acme', $env))->toBe('Acme');

    $filesColumn = new ListColumn('group_ids', 'files', 'res.groups');
    $adminGroup = $env->model('res.groups')->search([['name', '=', 'Admin']], limit: 1)->ids()[0];

    expect($builder->formatListCell($filesColumn, [$adminGroup], $env))->toContain('Admin');
});

test('arch schema builder formats many2many relation ids and empty values', function (): void {
    $env = $this->env;
    $builder = new ArchSchemaBuilder;
    $adminGroup = $env->model('res.groups')->search([['name', '=', 'Admin']], limit: 1)->ids()[0];
    $column = new ListColumn('group_ids', 'm2m', 'res.groups');

    expect($builder->formatListCell($column, [$adminGroup], $env))->toContain('Admin')
        ->and($builder->formatListCell($column, [], $env))->toBe('')
        ->and($builder->formatListCell(new ListColumn('name', 'text'), '', $env))->toBe('')
        ->and($builder->buildListColumns(['model' => '', 'fields' => [['name' => 'name']]], $env)[0]->kind)->toBe('text');
});

test('many2one list columns resolve through merged field set when partner is extended', function (): void {
    $roots = [
        dirname(__DIR__, 3).'/modules/modules',
        dirname(__DIR__, 3).'/modules/tests/fixtures',
    ];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base']);
    $installer->install('partners', $roots);
    $installer->install('partners_ext', $roots);

    $env = $installer->environment($roots);

    expect($env->registry->modelClass('res.partner'))->toBe(PartnerExtension::class);

    $columns = (new ArchSchemaBuilder)->buildListColumns(PartnerArch::list($env), $env);
    $country = collect($columns)->firstWhere('name', 'country_id');
    $company = collect($columns)->firstWhere('name', 'company_id');

    expect($country)->not->toBeNull()
        ->and($country->kind)->toBe('m2o')
        ->and($company)->not->toBeNull()
        ->and($company->kind)->toBe('m2o');
});
