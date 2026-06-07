<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\ModuleRepository;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('installing partners creates ORM tables and records', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;

    $installer->installBootstrap($roots, ['base']);
    $installer->install('partners', $roots);

    expect(Schema::hasTable('res_country'))->toBeTrue()
        ->and(Schema::hasTable('res_partner'))->toBeTrue();

    $env = $installer->environment($roots);
    $country = $env->model('res.country')->create(['name' => 'Module Test Country', 'code' => 'MT']);
    $partner = $env->model('res.partner')->create([
        'name' => 'Module Test Partner',
        'country_id' => $country->ids()[0],
    ]);

    expect($partner->read()[0]['name'])->toBe('Module Test Partner')
        ->and($partner->read()[0]['country_id'])->toBe($country->ids()[0]);
});

test('partners manifest is discovered with model classes', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $specs = (new ModuleInstaller)->discover($roots);

    expect($specs['partners']->models)->toHaveCount(2)
        ->and($specs['partners']->depends)->toBe(['base']);
});
