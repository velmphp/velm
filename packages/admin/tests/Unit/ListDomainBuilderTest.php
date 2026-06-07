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
