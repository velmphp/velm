<?php

declare(strict_types=1);

use Velm\Modules\Migrations\ModuleMigrationAutogen;
use Velm\Modules\ModuleSpec;
use Velm\Modules\ModuleVersion;
use Velm\Schema\SchemaDiff;

test('migration autogen renders stub and bumps fluent manifest version', function (): void {
    $dir = sys_get_temp_dir().'/velm_autogen_'.uniqid('', true);
    mkdir($dir, 0777, true);

    file_put_contents($dir.'/__velm__.php', <<<'PHP'
<?php
use Velm\Modules\Manifest;
return Manifest::make('demo')->version(0, 1, 0);
PHP);

    $spec = new ModuleSpec(
        name: 'demo',
        version: [0, 1, 0],
        depends: [],
        path: $dir,
    );

    $autogen = new ModuleMigrationAutogen;
    $to = ModuleVersion::nextMinorVersion([0, 1, 0]);
    $target = $autogen->write($spec, new SchemaDiff, [0, 1, 0], $to, dryRun: false);

    expect($target)->toEndWith('0_1_0_to_0_2_0.php')
        ->and(is_file($target))->toBeTrue()
        ->and(file_get_contents($dir.'/__velm__.php'))->toContain('->version(0, 2, 0)');

    @unlink($target);
    @unlink($dir.'/__velm__.php');
    @rmdir($dir.'/migrations');
    @rmdir($dir);
});

test('next minor version bumps minor and resets patch', function (): void {
    expect(ModuleVersion::nextMinorVersion([0, 1, 0]))->toBe([0, 2, 0])
        ->and(ModuleVersion::nextMinorVersion([1, 0]))->toBe([1, 1]);
});

test('migration autogen dry run returns preview without writing files', function (): void {
    $dir = sys_get_temp_dir().'/velm_autogen_dry_'.uniqid('', true);
    mkdir($dir, 0777, true);
    file_put_contents($dir.'/__velm__.php', "<?php\nuse Velm\\Modules\\Manifest;\nreturn Manifest::make('demo')->version(0, 1, 0);\n");

    $spec = new ModuleSpec(name: 'demo', version: [0, 1, 0], depends: [], path: $dir);
    $diff = new SchemaDiff;
    $diff->newColumns[] = ['demo_table', 'note'];
    $diff->alterations[] = new \Velm\Schema\SchemaAlteration('demo_table', 'note', 'add', 'new column');
    $diff->orphanColumns[] = ['demo_table', 'legacy'];

    $preview = (new ModuleMigrationAutogen)->write($spec, $diff, [0, 1, 0], [0, 2, 0], dryRun: true);

    expect($preview)->toContain('would write')
        ->and($preview)->toContain('demo_table.note')
        ->and(is_dir($dir.'/migrations'))->toBeFalse();

    @unlink($dir.'/__velm__.php');
    @rmdir($dir);
});

test('migration autogen targetVersion honors explicit version', function (): void {
    expect((new ModuleMigrationAutogen)->targetVersion([0, 1, 0], '1.2.3'))->toBe([1, 2, 3]);
});

test('migration autogen bumps array style manifest version', function (): void {
    $dir = sys_get_temp_dir().'/velm_autogen_array_'.uniqid('', true);
    mkdir($dir, 0777, true);
    file_put_contents($dir.'/__velm__.php', "<?php\nreturn ['NAME' => 'demo', 'VERSION' => ['0', '1', '0'], 'DEPENDS' => []];\n");

    (new ModuleMigrationAutogen)->bumpManifestVersion($dir.'/__velm__.php', [0, 1, 0], [0, 2, 0]);

    expect(file_get_contents($dir.'/__velm__.php'))->toContain("'VERSION' => ['0', '2', '0']");

    @unlink($dir.'/__velm__.php');
    @rmdir($dir);
});

test('migration autogen refuses to overwrite existing migration', function (): void {
    $dir = sys_get_temp_dir().'/velm_autogen_dup_'.uniqid('', true);
    mkdir($dir.'/migrations', 0777, true);
    file_put_contents($dir.'/__velm__.php', "<?php\nuse Velm\\Modules\\Manifest;\nreturn Manifest::make('demo')->version(0, 1, 0);\n");
    file_put_contents($dir.'/migrations/0_1_0_to_0_2_0.php', "<?php\nreturn static fn () => null;\n");

    $spec = new ModuleSpec(name: 'demo', version: [0, 1, 0], depends: [], path: $dir);

    expect(fn () => (new ModuleMigrationAutogen)->write($spec, new SchemaDiff, [0, 1, 0], [0, 2, 0], dryRun: false))
        ->toThrow(RuntimeException::class, 'Refusing to overwrite');

    @unlink($dir.'/migrations/0_1_0_to_0_2_0.php');
    @unlink($dir.'/__velm__.php');
    @rmdir($dir.'/migrations');
    @rmdir($dir);
});
