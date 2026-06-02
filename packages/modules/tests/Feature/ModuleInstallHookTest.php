<?php

declare(strict_types=1);

use Velm\Modules\ModuleInstaller;
use Velm\Modules\Tests\Support\InstallHookProbe;
use Velm\Modules\Tests\Support\VersionedDemo;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }

    InstallHookProbe::$calls = 0;
});

test('install hook runs once on first install not on upgrade sync path', function (): void {
    $root = sys_get_temp_dir().'/velm_install_hook_'.uniqid('', true);
    $modulePath = $root.'/hook_demo';
    mkdir($modulePath, 0777, true);

    file_put_contents($modulePath.'/__velm__.php', <<<'PHP'
<?php
use Velm\Modules\Manifest;
use Velm\Modules\Tests\Support\InstallHookProbe;
use Velm\Modules\Tests\Support\VersionedDemo;
return Manifest::make('hook_demo')
    ->version(0, 1, 0)
    ->models(VersionedDemo::class)
    ->installHook(InstallHookProbe::class);
PHP);

    $installer = new ModuleInstaller;
    $roots = [$root];

    $installer->migrate('hook_demo', $roots);

    expect(InstallHookProbe::$calls)->toBe(1);

    InstallHookProbe::$calls = 0;
    $installer->sync('hook_demo', $roots);

    expect(InstallHookProbe::$calls)->toBe(0);

    @unlink($modulePath.'/__velm__.php');
    @rmdir($modulePath);
    @rmdir($root);
});
