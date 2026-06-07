<?php

declare(strict_types=1);

namespace Velm\Modules\Tests;

class DialectSmokeTestCase extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));

        $demoAddons = dirname(__DIR__, 3).'/apps/demo/addons';

        if (is_dir($demoAddons)) {
            $app['config']->set('velm.addon_autoload_paths', [$demoAddons]);
        }

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', $this->ciDatabaseConnection());
    }

    /**
     * @return array<string, mixed>
     */
    private function ciDatabaseConnection(): array
    {
        $driver = getenv('DB_CONNECTION') ?: 'sqlite';

        if (! in_array($driver, ['mysql', 'pgsql'], true)) {
            return [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ];
        }

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

        return $connection;
    }
}
