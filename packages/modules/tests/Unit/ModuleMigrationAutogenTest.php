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
