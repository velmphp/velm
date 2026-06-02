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
    ]);
});

test('velm manager installs modules and exposes environment', function (): void {
    $manager = app(VelmManager::class);

    expect($manager->addonPaths())->not->toBeEmpty();

    $manager->installBootstrap(['base']);
    $manager->install('partners');

    expect(app(ModuleRepository::class)->isInstalled('partners'))->toBeTrue();

    $partner = $manager->environment()->model('res.partner')->create(['name' => 'Acme']);

    expect($partner->read()[0]['name'])->toBe('Acme');
});
