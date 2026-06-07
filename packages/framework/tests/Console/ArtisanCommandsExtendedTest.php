<?php

declare(strict_types=1);

use Velm\Console\Tests\Support\ResPartnerSchemaHelper;
use Velm\Framework\Tests\TestCase;
use Velm\Framework\VelmManager;
use Velm\Modules\ModuleInstaller;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }

    config([
        'velm.addon_paths' => [dirname(__DIR__, 3).'/modules/modules'],
        'velm.bootstrap_modules' => ['base'],
    ]);
});

test('artisan velm db status warns when no modules installed', function (): void {
    $this->artisan('velm:db:status')
        ->expectsOutputToContain('No installed modules')
        ->assertSuccessful();
});

test('artisan velm db status prints table for installed modules', function (): void {
    app(VelmManager::class)->installBootstrap();
    app(VelmManager::class)->install('partners');

    $this->artisan('velm:db:status')
        ->expectsOutputToContain('partners')
        ->assertSuccessful();
});

test('artisan velm db diff fails without module option', function (): void {
    $this->artisan('velm:db:diff')
        ->assertFailed();
});

test('artisan velm db diff reports no drift when schema matches', function (): void {
    app(VelmManager::class)->installBootstrap();
    app(VelmManager::class)->install('partners');

    $this->artisan('velm:db:diff', ['--module' => 'partners'])
        ->expectsOutputToContain('No schema drift')
        ->assertSuccessful();
});

test('artisan velm db diff reports new table and column drift', function (): void {
    app(VelmManager::class)->installBootstrap();
    app(VelmManager::class)->install('partners');

    \Illuminate\Support\Facades\DB::statement('ALTER TABLE "res_partner" DROP COLUMN "is_company"');

    $this->artisan('velm:db:diff', ['--module' => 'partners'])
        ->expectsOutputToContain('+ column res_partner.is_company')
        ->assertSuccessful();
});

test('artisan velm db diff reports set_not_null guidance', function (): void {
    app(VelmManager::class)->installBootstrap();
    app(VelmManager::class)->install('partners');
    ResPartnerSchemaHelper::recreateWithNullableName(withNullNameRow: true);

    $this->artisan('velm:db:diff', ['--module' => 'partners'])
        ->expectsOutputToContain('set_not_null')
        ->expectsOutputToContain('NULL row(s)')
        ->assertSuccessful();
});

test('artisan velm db diff reports set_not_null when no null rows remain', function (): void {
    app(VelmManager::class)->installBootstrap();
    app(VelmManager::class)->install('partners');
    ResPartnerSchemaHelper::recreateWithNullableName(withNullNameRow: false);

    $this->artisan('velm:db:diff', ['--module' => 'partners'])
        ->expectsOutputToContain('no NULL rows')
        ->assertSuccessful();
});

test('artisan velm db diff fails for unknown module', function (): void {
    $this->artisan('velm:db:diff', ['--module' => 'missing_module_xyz'])
        ->assertFailed();
});

test('artisan velm db autogen fails without module option', function (): void {
    $this->artisan('velm:db:autogen')
        ->assertFailed();
});

test('artisan velm db autogen fails for unknown module', function (): void {
    $this->artisan('velm:db:autogen', ['--module' => 'missing_module_xyz'])
        ->assertFailed();
});

test('artisan velm db autogen writes migration file', function (): void {
    $root = sys_get_temp_dir().'/velm-fw-autogen-'.uniqid('', true);
    $modulePath = $root.'/autogen_fw';
    mkdir($modulePath.'/migrations', 0777, true);

    file_put_contents(
        $modulePath.'/__velm__.php',
        "<?php\n\ndeclare(strict_types=1);\n\nuse Velm\\Modules\\Manifest;\n\nreturn Manifest::make('autogen_fw')\n"
        ."    ->version(0, 1, 0)\n"
        ."    ->depends('base')\n"
        ."    ->summary('Autogen fw');\n",
    );

    config([
        'velm.addon_paths' => [
            dirname(__DIR__, 3).'/modules/modules',
            $root,
        ],
    ]);

    app(VelmManager::class)->installBootstrap();
    app(VelmManager::class)->install('autogen_fw');

    $this->artisan('velm:db:autogen', ['--module' => 'autogen_fw', '--target-version' => '0.2.0'])
        ->expectsOutputToContain('Wrote')
        ->expectsOutputToContain('Bumped VERSION')
        ->assertSuccessful();
});

test('artisan velm db autogen with views scaffolds for schema diff', function (): void {
    app(VelmManager::class)->installBootstrap();
    app(VelmManager::class)->install('partners');
    \Illuminate\Support\Facades\DB::statement('ALTER TABLE "res_partner" DROP COLUMN "is_company"');

    $this->artisan('velm:db:autogen', [
        '--module' => 'partners',
        '--with-views' => true,
        '--dry-run' => true,
    ])->assertSuccessful();
});

test('artisan velm migrate installs bootstrap modules without module option', function (): void {
    $this->artisan('velm:migrate')
        ->expectsOutputToContain('bootstrap')
        ->assertSuccessful();
});

test('artisan velm migrate fails for unknown module', function (): void {
    $this->artisan('velm:migrate', ['--module' => 'missing_module_xyz'])
        ->assertFailed();
});

test('artisan velm module install fails for unknown module', function (): void {
    $this->artisan('velm:module:install', ['module' => 'missing_module_xyz'])
        ->assertFailed();
});

test('artisan velm module sync fails for unknown module', function (): void {
    $this->artisan('velm:module:sync', ['module' => 'missing_module_xyz'])
        ->assertFailed();
});

test('artisan velm module uninstall partners with drop schema in testing', function (): void {
    app(VelmManager::class)->installBootstrap();
    app(VelmManager::class)->install('partners');

    $this->artisan('velm:module:uninstall', [
        'module' => 'partners',
        '--drop-schema' => true,
    ])
        ->expectsOutputToContain('tables were dropped')
        ->assertSuccessful();
});

test('module uninstall command rejects drop schema outside testing', function (): void {
    $previousEnv = $this->app['env'];
    $this->app['env'] = 'production';

    try {
        $this->artisan('velm:module:uninstall', [
            'module' => 'partners',
            '--drop-schema' => true,
        ])->assertFailed();
    } finally {
        $this->app['env'] = $previousEnv;
    }
});

test('artisan velm module uninstall partners succeeds', function (): void {
    app(VelmManager::class)->installBootstrap();
    app(VelmManager::class)->install('partners');

    $this->artisan('velm:module:uninstall', ['module' => 'partners'])
        ->expectsOutputToContain('Uninstalled partners')
        ->assertSuccessful();
});

test('artisan velm module sync all warns when no modules installed', function (): void {
    $this->artisan('velm:module:sync-all')
        ->expectsOutputToContain('No installed modules')
        ->assertSuccessful();
});

test('artisan velm seed with module option succeeds', function (): void {
    app(VelmManager::class)->installBootstrap();

    $this->artisan('velm:seed', ['--module' => 'base'])
        ->expectsOutputToContain('Velm seed completed')
        ->assertSuccessful();
});

test('artisan velm seed fails for unknown module', function (): void {
    app(VelmManager::class)->installBootstrap();

    $this->artisan('velm:seed', ['--module' => 'missing_module_xyz'])
        ->assertFailed();
});

test('artisan velm cron run reports executed jobs', function (): void {
    app(VelmManager::class)->installBootstrap();
    app(VelmManager::class)->install('partners');
    $env = app(VelmManager::class)->environment();

    $action = $env->model('ir.actions.server')->create([
        'name' => 'Tick partners',
        'model' => 'res.partner',
        'action_type' => 'write',
        'vals_json' => json_encode(['active' => true]),
    ]);

    $past = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->modify('-1 hour')->format('Y-m-d H:i:s');

    $env->model('ir.cron')->create([
        'name' => 'Velm test cron',
        'action_id' => $action->ids()[0],
        'interval_number' => 1,
        'interval_type' => 'hours',
        'nextcall' => $past,
        'active' => true,
    ]);

    $this->artisan('velm:cron:run')->assertSuccessful();
});

test('artisan velm module uninstall prints blockers for protected base module', function (): void {
    app(VelmManager::class)->installBootstrap();

    $this->artisan('velm:module:uninstall', ['module' => 'base'])
        ->expectsOutputToContain('protected system module')
        ->assertFailed();
});

test('artisan velm module sync all syncs installed modules', function (): void {
    app(VelmManager::class)->installBootstrap();
    app(VelmManager::class)->install('partners');

    $this->artisan('velm:module:sync-all')
        ->expectsOutputToContain('Synced partners')
        ->assertSuccessful();
});

test('artisan velm cron run reports no due jobs', function (): void {
    app(VelmManager::class)->installBootstrap();

    $this->artisan('velm:cron:run')
        ->expectsOutputToContain('No due cron jobs')
        ->assertSuccessful();
});

test('artisan velm cron run fails when environment cannot be resolved', function (): void {
    app()->instance(ModuleInstaller::class, new class extends ModuleInstaller
    {
        public function environment(array $roots): \Velm\Environment
        {
            throw new \RuntimeException('Environment unavailable');
        }
    });

    $this->artisan('velm:cron:run')
        ->expectsOutputToContain('Environment unavailable')
        ->assertFailed();
});

test('artisan velm make view fails without module context', function (): void {
    $this->artisan('velm:make:view', ['model' => 'unknown.model'])
        ->assertFailed();
});

test('artisan velm make model fails without module context', function (): void {
    $this->artisan('velm:make:model', ['model' => 'unknown.model'])
        ->assertFailed();
});

test('artisan velm make menu fails without view option', function (): void {
    $this->artisan('velm:make:menu')
        ->assertFailed();
});

test('artisan velm make menu fails without module context', function (): void {
    $this->artisan('velm:make:menu', ['--view' => 'product.list'])
        ->assertFailed();
});
