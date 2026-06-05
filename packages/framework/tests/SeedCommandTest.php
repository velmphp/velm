<?php

declare(strict_types=1);

use Velm\Environment;
use Velm\Framework\Tests\TestCase;
use Velm\Framework\VelmManager;
use Velm\Modules\Manifest;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('seed runs manifest seeders and is idempotent', function (): void {
    if (! is_dir(sys_get_temp_dir())) {
        skip('sys temp dir is not available.');
    }

    $root = rtrim(sys_get_temp_dir(), '/\\').'/velm_seed_demo_'.bin2hex(random_bytes(6));
    $modulePath = $root.'/seed_demo';

    mkdir($modulePath, 0777, true);

    // Use the fluent manifest builder format.
    file_put_contents(
        $modulePath.'/__velm__.php',
        "<?php\n\ndeclare(strict_types=1);\n\nuse Velm\\Modules\\Manifest;\n\nreturn Manifest::make('seed_demo')\n"
        ."    ->version(0, 1, 0)\n"
        ."    ->depends('base')\n"
        ."    ->seeders(".TestSeedDemoSeeder::class."::class)\n"
        ."    ->summary('Seed demo');\n",
    );

    config([
        'velm.addon_paths' => [
            dirname(__DIR__, 2).'/modules/modules',
            $root,
        ],
        'velm.bootstrap_modules' => ['base'],
    ]);

    $velm = app(VelmManager::class);
    $velm->installBootstrap();

    $velm->install('seed_demo');

    $velm->seed();
    $velm->seed();

    $env = $velm->environment();
    expect($env->model('res.groups')->search([['name', '=', 'Seed Demo Group']])->count())->toBe(1);
});

final class TestSeedDemoSeeder
{
    public static function run(Environment $env): void
    {
        if ($env->model('res.groups')->search([['name', '=', 'Seed Demo Group']])->count() > 0) {
            return;
        }

        $env->model('res.groups')->create(['name' => 'Seed Demo Group']);
    }
}

