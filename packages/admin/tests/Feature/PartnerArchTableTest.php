<?php

declare(strict_types=1);

use Velm\Admin\Arch\ArchTableConfigurator;
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

test('table configurator loads partner rows from the recordset', function (): void {
    $this->env->model('res.country')->create(['name' => 'Belgium', 'code' => 'BE']);
    $this->env->model('res.partner')->create([
        'name' => 'Velm SA',
        'active' => true,
        'country_id' => 1,
    ]);

    $records = (new ArchTableConfigurator)->fetchRecords(PartnerArch::list($this->env), $this->env);

    expect($records)->toHaveCount(1)
        ->and($records->first()['name'])->toBe('Velm SA')
        ->and($records->first()['active'])->toBeTrue();
});
