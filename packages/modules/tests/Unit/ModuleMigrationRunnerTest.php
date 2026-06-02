<?php

declare(strict_types=1);

use Velm\Database\PdoConnection;
use Velm\Environment;
use Velm\Modules\Migrations\ModuleMigrationRunner;
use Velm\Modules\ModuleSpec;
use Velm\Registry;

test('migration runner executes applicable scripts in order', function (): void {
    $directory = sys_get_temp_dir().'/velm_migrate_'.uniqid('', true);
    mkdir($directory.'/migrations', 0777, true);

    file_put_contents($directory.'/migrations/0_1_0_to_0_2_0.php', <<<'PHP'
<?php
return static function ($env): void {
    $GLOBALS['velm_migrate_ran'][] = '0_2';
};
PHP);

    file_put_contents($directory.'/migrations/0_2_0_to_0_3_0.php', <<<'PHP'
<?php
return static function ($env): void {
    $GLOBALS['velm_migrate_ran'][] = '0_3';
};
PHP);

    $GLOBALS['velm_migrate_ran'] = [];

    $spec = new ModuleSpec(
        name: 'demo',
        version: [0, 3, 0],
        depends: [],
        path: $directory,
    );

    $env = new Environment(PdoConnection::sqliteMemory(), new Registry);

    (new ModuleMigrationRunner)->run($env, $spec, [0, 1, 0], [0, 3, 0]);

    expect($GLOBALS['velm_migrate_ran'])->toBe(['0_2', '0_3']);

    array_map(unlink(...), glob($directory.'/migrations/*.php') ?: []);
    rmdir($directory.'/migrations');
    rmdir($directory);
});
