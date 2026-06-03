<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Velm\Modules\AppsCatalog;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\ModuleRepository;
use Velm\Modules\Tests\Support\VersionedDemo;
use Velm\Modules\Tests\Support\VersionedDemoV2;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('reconcile applies schema when manifest version is unchanged', function (): void {
    $root = sys_get_temp_dir().'/velm_reconcile_'.uniqid('', true);
    $modulePath = $root.'/versioned_demo';
    mkdir($modulePath, 0777, true);

    file_put_contents($modulePath.'/__velm__.php', <<<'PHP'
<?php
use Velm\Modules\Manifest;
use Velm\Modules\Tests\Support\VersionedDemo;
return Manifest::make('versioned_demo')->version(0, 1, 0)->models(VersionedDemo::class);
PHP);

    $installer = new ModuleInstaller;
    $roots = [$root];

    $installer->migrate('versioned_demo', $roots);

    expect($installer->hasPendingSchemaDiff('versioned_demo', $roots))->toBeFalse();

    file_put_contents($modulePath.'/__velm__.php', <<<'PHP'
<?php
use Velm\Modules\Manifest;
use Velm\Modules\Tests\Support\VersionedDemoV2;
return Manifest::make('versioned_demo')->version(0, 1, 0)->models(VersionedDemoV2::class);
PHP);

    expect($installer->hasPendingSchemaDiff('versioned_demo', $roots))->toBeTrue()
        ->and($installer->diff('versioned_demo', $roots)->isSyncActionable($installer->canAlterColumnNullability()))->toBeTrue();

    $entry = (new AppsCatalog)->entry($roots, 'versioned_demo');

    expect($entry)->not->toBeNull()
        ->and($entry['state'])->toBe('needs_sync')
        ->and($entry['version_upgrade'])->toBeFalse()
        ->and($entry['has_schema_diff'])->toBeTrue();

    $installer->reconcile('versioned_demo', $roots);

    expect($installer->hasPendingSchemaDiff('versioned_demo', $roots))->toBeFalse()
        ->and((new AppsCatalog)->entry($roots, 'versioned_demo')['state'])->toBe('installed');

    $columns = array_map(
        static fn (object $row): string => (string) $row->name,
        DB::select('PRAGMA table_info(versioned_demo)'),
    );

    expect($columns)->toContain('code');

    foreach (glob($modulePath.'/*') ?: [] as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }
    @rmdir($modulePath);
    @rmdir($root);
});

test('upgrade via reconcile runs version migrations then clears to_upgrade state', function (): void {
    $root = sys_get_temp_dir().'/velm_reconcile_ver_'.uniqid('', true);
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

    $entry = (new AppsCatalog)->entry($roots, 'versioned_demo');

    expect($entry['state'])->toBe('to_upgrade')
        ->and($entry['version_upgrade'])->toBeTrue();

    $installer->reconcile('versioned_demo', $roots);

    expect(DB::table(ModuleRepository::TABLE)->where('name', 'versioned_demo')->value('version'))
        ->toBe('0.2.0')
        ->and((new AppsCatalog)->entry($roots, 'versioned_demo')['state'])->toBe('installed');

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
