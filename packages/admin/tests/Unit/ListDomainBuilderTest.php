<?php

declare(strict_types=1);

use Velm\Admin\Arch\ListDomainBuilder;
use Velm\Admin\Arch\ListQuery;
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

test('list domain builder adds ilike search across char fields', function (): void {
    $this->env->model('res.partner')->create(['name' => 'Acme Corp', 'active' => true]);
    $this->env->model('res.partner')->create(['name' => 'Other LLC', 'active' => true]);

    $arch = PartnerArch::list($this->env);
    $domain = (new ListDomainBuilder)->build($arch, $this->env, new ListQuery(search: 'acme'));
    $rows = $this->env->model('res.partner')->search($domain)->read();

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['name'])->toBe('Acme Corp');
});

test('list domain builder supports id numeric and char filter chips', function (): void {
    $this->env->model('res.partner')->create(['name' => 'Chip Target', 'active' => true]);
    $arch = PartnerArch::list($this->env);
    $partnerId = $this->env->model('res.partner')->search([['name', '=', 'Chip Target']])->ids()[0];

    $idDomain = (new ListDomainBuilder)->build($arch, $this->env, new ListQuery(
        filterChips: [[
            'field' => 'id',
            'op' => '=',
            'value' => (string) $partnerId,
            'label' => 'ID',
        ]],
    ));

    expect($this->env->model('res.partner')->search($idDomain)->count())->toBe(1);

    $nameDomain = (new ListDomainBuilder)->build($arch, $this->env, new ListQuery(
        filterChips: [[
            'field' => 'name',
            'op' => '=',
            'value' => 'Chip',
            'label' => 'Name',
        ]],
    ));

    expect(collect($this->env->model('res.partner')->search($nameDomain)->read())->pluck('name'))->toContain('Chip Target');
});

test('list domain builder ignores invalid filter chips and numeric comparisons', function (): void {
    $roots = [dirname(__DIR__, 3).'/modules/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $env->model('workflow.instance')->create([
        'definition_id' => $env->model('workflow.definition')->search(limit: 1)->ids()[0],
        'res_model' => 'res.partner',
        'res_id' => 5000,
        'state' => 'draft',
    ]);

    $arch = [
        'model' => 'workflow.instance',
        'fields' => [
            ['name' => 'res_id'],
            ['name' => 'state'],
        ],
    ];

    $domain = (new ListDomainBuilder)->build($arch, $env, new ListQuery(
        filterChips: [
            ['field' => null, 'op' => '=', 'value' => 'x', 'label' => 'Bad'],
            ['field' => 'missing_field', 'op' => '=', 'value' => 'x', 'label' => 'Bad'],
            ['field' => 'id', 'op' => '=', 'value' => 'not-a-number', 'label' => 'Bad'],
            ['field' => 'state', 'op' => '=', 'value' => '', 'label' => 'Bad'],
            ['field' => 'res_id', 'op' => '>=', 'value' => 1000, 'label' => 'Record'],
            ['field' => 'res_id', 'op' => 'like', 'value' => 1, 'label' => 'Bad op'],
        ],
    ));

    $rows = $env->model('workflow.instance')->search($domain)->read();

    expect(collect($rows)->pluck('res_id'))->toContain(5000);
});

test('list domain builder searches hidden char fields when visible columns omit them', function (): void {
    $this->env->model('res.partner')->create(['name' => 'Hidden Search Co', 'active' => true]);

    $arch = [
        'model' => 'res.partner',
        'fields' => [
            ['name' => 'active', 'widget' => 'toggle'],
        ],
    ];

    $domain = (new ListDomainBuilder)->build($arch, $this->env, new ListQuery(search: 'Hidden Search'));
    $rows = $this->env->model('res.partner')->search($domain)->read();

    expect(collect($rows)->pluck('name'))->toContain('Hidden Search Co');
});

test('list domain builder ignores chips for unknown model fields', function (): void {
    $arch = [
        'model' => 'res.partner',
        'fields' => [['name' => 'ghost_field']],
    ];

    $domain = (new ListDomainBuilder)->build($arch, $this->env, new ListQuery(
        filterChips: [[
            'field' => 'ghost_field',
            'op' => '=',
            'value' => 'x',
            'label' => 'Ghost',
        ]],
    ));

    expect($domain)->toBe([]);
});

test('list domain builder adds boolean filter chips', function (): void {
    $this->env->model('res.partner')->create(['name' => 'Active Co', 'active' => true]);
    $this->env->model('res.partner')->create(['name' => 'Inactive Co', 'active' => false]);

    $arch = PartnerArch::list($this->env);
    $domain = (new ListDomainBuilder)->build($arch, $this->env, new ListQuery(
        filterChips: [[
            'field' => 'active',
            'op' => '=',
            'value' => true,
            'label' => 'Active: Yes',
        ]],
    ));

    $rows = $this->env->model('res.partner')->search($domain)->read();

    expect($rows)->not->toBeEmpty()
        ->and(collect($rows)->pluck('name'))->toContain('Active Co')
        ->and(collect($rows)->every(static fn (array $row): bool => ($row['active'] ?? false) === true))->toBeTrue();
});
