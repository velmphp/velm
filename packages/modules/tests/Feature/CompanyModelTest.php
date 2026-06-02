<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('base install creates res.company and seeds a default company', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;

    $installer->installBootstrap($roots, ['base']);

    expect(Schema::hasTable('res_company'))->toBeTrue();

    $env = $installer->environment($roots);
    $companies = $env->model('res.company')->search()->read();

    expect($companies)->toHaveCount(1)
        ->and($companies[0]['name'])->toBe('My Company');
});
