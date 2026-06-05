<?php

declare(strict_types=1);

use Velm\Framework\Tests\TestCase;
use Velm\Framework\VelmManager;
use Velm\Modules\ModuleRepository;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }

    config([
        'velm.addon_paths' => [dirname(__DIR__, 2).'/modules/modules'],
        'velm.bootstrap_modules' => ['base'],
    ]);
});

test('migrate fresh drops velm tables and reinstalls requested modules', function (): void {
    $velm = app(VelmManager::class);

    $velm->installBootstrap();
    $velm->install('partners');

    $env = $velm->environment();
    $partner = $env->model('res.partner')->create(['name' => 'Acme']);

    expect($partner->count())->toBe(1)
        ->and(app(ModuleRepository::class)->isInstalled('partners'))->toBeTrue();

    $velm->migrateFresh(modules: ['partners']);

    $freshEnv = $velm->environment();

    expect($freshEnv->model('res.partner')->search()->count())->toBe(0)
        ->and(app(ModuleRepository::class)->isInstalled('partners'))->toBeTrue()
        ->and(app(ModuleRepository::class)->isInstalled('base'))->toBeTrue();
});

