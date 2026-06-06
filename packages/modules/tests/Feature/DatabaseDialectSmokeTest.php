<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\ModuleRepository;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

test('install bootstrap and partners on CI database driver', function (): void {
    $driver = getenv('DB_CONNECTION') ?: 'sqlite';

    $connection = [
        'driver' => $driver,
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: ($driver === 'pgsql' ? '5432' : '3306'),
        'database' => getenv('DB_DATABASE') ?: 'velm_test',
        'username' => getenv('DB_USERNAME') ?: ($driver === 'pgsql' ? 'postgres' : 'root'),
        'password' => getenv('DB_PASSWORD') ?: ($driver === 'pgsql' ? 'postgres' : 'root'),
        'prefix' => '',
        'foreign_key_constraints' => true,
    ];

    if ($driver === 'mysql') {
        $connection['charset'] = 'utf8mb4';
    }

    config([
        'database.default' => 'testing',
        'database.connections.testing' => $connection,
    ]);

    DB::purge('testing');
    DB::reconnect('testing');

    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;

    $installer->installBootstrap($roots, ['base']);
    $installer->install('partners', $roots);

    expect(Schema::hasTable(ModuleRepository::TABLE))->toBeTrue()
        ->and(Schema::hasTable('res_partner'))->toBeTrue()
        ->and($installer->environment($roots)->model('res.partner')->search()->count())->toBeGreaterThanOrEqual(0);
})->skip(
    fn (): bool => ! in_array(getenv('DB_CONNECTION') ?: 'sqlite', ['mysql', 'pgsql'], true),
    'Dialect smoke runs only on mysql/pgsql CI matrix.',
);
