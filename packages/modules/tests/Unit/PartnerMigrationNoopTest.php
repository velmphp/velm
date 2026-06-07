<?php

declare(strict_types=1);

use Velm\Modules\ModuleInstaller;
use Velm\Modules\Tests\Support\ModuleRoots;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

test('partner no-op migrations are callable', function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }

    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);

    $env = $installer->environment($roots);
    $migrationDir = dirname(__DIR__, 2).'/modules/partners/migrations';

    $initial = require $migrationDir.'/0_to_0.php';
    $bump = require $migrationDir.'/0_2_0_to_0.php';

    expect($initial)->toBeCallable()
        ->and($bump)->toBeCallable();

    $initial($env);
    $bump($env);
});
