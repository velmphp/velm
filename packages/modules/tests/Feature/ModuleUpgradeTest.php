<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\ModuleRepository;
use Velm\Modules\Tests\Support\VersionedDemo;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('migrate upgrades module when manifest version increases', function (): void {
    $root = sys_get_temp_dir().'/velm_upgrade_'.uniqid('', true);
    $modulePath = $root.'/versioned_demo';
    mkdir($modulePath.'/migrations', 0777, true);

    file_put_contents($modulePath.'/__velm__.php', <<<'PHP'
<?php
use Velm\Modules\Manifest;
use Velm\Modules\Tests\Support\VersionedDemo;
return Manifest::make('versioned_demo')->version(0, 1, 0)->models(VersionedDemo::class);
PHP);

    $installer = new ModuleInstaller;
    $roots = [$root];

    $installer->migrate('versioned_demo', $roots);

    expect(DB::table(ModuleRepository::TABLE)->where('name', 'versioned_demo')->value('version'))
        ->toBe('0.1.0');

    file_put_contents($modulePath.'/__velm__.php', <<<'PHP'
<?php
use Velm\Modules\Manifest;
use Velm\Modules\Tests\Support\VersionedDemoV2;
return Manifest::make('versioned_demo')->version(0, 2, 0)->models(VersionedDemoV2::class);
PHP);

    file_put_contents($modulePath.'/migrations/0_1_0_to_0_2_0.php', <<<'PHP'
<?php
return static function (): void {};
PHP);

    $installer->migrate('versioned_demo', $roots);

    expect(DB::table(ModuleRepository::TABLE)->where('name', 'versioned_demo')->value('version'))
        ->toBe('0.2.0');

    $columns = array_map(
        static fn (object $row): string => (string) $row->name,
        DB::select('PRAGMA table_info(versioned_demo)'),
    );

    expect($columns)->toContain('code');

    foreach (glob($modulePath.'/migrations/*') ?: [] as $file) {
        @unlink($file);
    }
    @rmdir($modulePath.'/migrations');

    foreach (glob($modulePath.'/*') ?: [] as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }
    @rmdir($modulePath);
    @rmdir($root);
});

test('module installer diff reports pending column', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'partners']);

    $diff = $installer->diff('partners', $roots);

    expect($diff->isEmpty())->toBeTrue();
});
