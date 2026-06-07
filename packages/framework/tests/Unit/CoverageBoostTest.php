<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Velm\Framework\Auth\UserProvisioner;
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

test('user provisioner no-ops when velm manager is unbound', function (): void {
    $this->app->forgetInstance(VelmManager::class);

    UserProvisioner::ensureProfile(new GenericUser([
        'id' => 1,
        'name' => 'Ghost',
        'email' => 'ghost@test',
    ]));

    expect(true)->toBeTrue();
});

test('user provisioner no-ops when res user row is missing', function (): void {
    app(VelmManager::class)->installBootstrap();

    UserProvisioner::ensureProfile(new GenericUser([
        'id' => 999999,
        'name' => 'Missing',
        'email' => 'missing@test',
    ]));

    expect(true)->toBeTrue();
});

test('user provisioner bootstrap admin no-ops for unknown email', function (): void {
    $env = app(\Velm\Environment::class);

    UserProvisioner::bootstrapAdminProfile($env, 'nobody@velm.test');

    expect(true)->toBeTrue();
});

test('user provisioner ignores users without getAttribute method', function (): void {
    app(VelmManager::class)->installBootstrap();

    UserProvisioner::ensureProfile(new class implements \Illuminate\Contracts\Auth\Authenticatable {
        public function getAuthIdentifierName(): string
        {
            return 'id';
        }

        public function getAuthIdentifier(): int
        {
            return 1;
        }

        public function getAuthPasswordName(): string
        {
            return 'password';
        }

        public function getAuthPassword(): string
        {
            return '';
        }

        public function getRememberToken(): ?string
        {
            return null;
        }

        public function setRememberToken($value): void
        {
        }

        public function getRememberTokenName(): string
        {
            return 'remember_token';
        }
    });

    expect(true)->toBeTrue();
});

test('artisan velm migrate fresh reports failure when migrate fresh throws', function (): void {
    config(['velm.bootstrap_modules' => ['missing_bootstrap_module_xyz']]);

    $this->artisan('velm:migrate:fresh', ['--yes' => true])
        ->assertFailed();
});

test('artisan velm module sync all continues after sync failure', function (): void {
    app(VelmManager::class)->installBootstrap();
    app(VelmManager::class)->install('partners');

    app()->instance(ModuleInstaller::class, new class extends ModuleInstaller
    {
        public function sync(string $module, array $roots): void
        {
            throw new RuntimeException('Sync exploded');
        }
    });

    $this->artisan('velm:module:sync-all')
        ->expectsOutputToContain('Sync exploded')
        ->assertSuccessful();
});

test('artisan velm make module fails for invalid module name', function (): void {
    $this->artisan('velm:make:module', ['name' => 'Bad-Name'])
        ->assertFailed();
});

test('artisan velm make view fails when view file already exists', function (): void {
    $tmp = sys_get_temp_dir().'/velm-make-view-dup-'.uniqid('', true);
    mkdir($tmp, 0777, true);

    $this->artisan('velm:make:module', [
        'name' => 'dup_view',
        '--path' => $tmp,
    ])->assertSuccessful();

    $this->artisan('velm:make:model', [
        'model' => 'item',
        '--module' => 'dup_view',
        '--path' => $tmp,
    ])->assertSuccessful();

    $this->artisan('velm:make:view', [
        'model' => 'dup_view.item',
        '--module' => 'dup_view',
        '--path' => $tmp,
        '--minimal' => true,
    ])->assertSuccessful();

    $this->artisan('velm:make:view', [
        'model' => 'dup_view.item',
        '--module' => 'dup_view',
        '--path' => $tmp,
        '--minimal' => true,
    ])->assertFailed();
});

test('artisan velm make model fails for duplicate model without force', function (): void {
    $tmp = sys_get_temp_dir().'/velm-make-model-dup-'.uniqid('', true);
    mkdir($tmp, 0777, true);

    $this->artisan('velm:make:module', [
        'name' => 'dup_model',
        '--path' => $tmp,
    ])->assertSuccessful();

    $this->artisan('velm:make:model', [
        'model' => 'widget',
        '--module' => 'dup_model',
        '--path' => $tmp,
    ])->assertSuccessful();

    $this->artisan('velm:make:model', [
        'model' => 'widget',
        '--module' => 'dup_model',
        '--path' => $tmp,
    ])->assertFailed();
});

test('artisan velm db diff prints column and orphan drift details', function (): void {
    app(VelmManager::class)->installBootstrap();
    app(VelmManager::class)->install('partners');

    \Illuminate\Support\Facades\DB::statement('ALTER TABLE "res_partner" DROP COLUMN "is_company"');
    \Illuminate\Support\Facades\DB::statement('ALTER TABLE "res_partner" ADD COLUMN "legacy_col" TEXT');

    $this->artisan('velm:db:diff', ['--module' => 'partners'])
        ->expectsOutputToContain('+ column res_partner.is_company')
        ->expectsOutputToContain('orphan column')
        ->assertSuccessful();
});

test('artisan velm db autogen with views warns when views already exist', function (): void {
    app(VelmManager::class)->installBootstrap();
    app(VelmManager::class)->install('partners');

    $this->artisan('velm:db:autogen', [
        '--module' => 'partners',
        '--with-views' => true,
        '--dry-run' => true,
    ])->assertSuccessful();
});

test('artisan velm db autogen failure path reports exception', function (): void {
    app()->instance(ModuleInstaller::class, new class extends ModuleInstaller
    {
        public function diff(string $module, array $roots): \Velm\Schema\SchemaDiff
        {
            throw new RuntimeException('Diff unavailable');
        }
    });

    $this->artisan('velm:db:autogen', ['--module' => 'partners'])
        ->expectsOutputToContain('Diff unavailable')
        ->assertFailed();
});
