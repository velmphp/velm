<?php

declare(strict_types=1);

use Velm\Framework\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('artisan velm module list shows partners module', function (): void {
    $this->artisan('velm:module:list')
        ->assertSuccessful();
});

test('artisan velm seed runs without error', function (): void {
    $this->artisan('velm:seed')
        ->assertSuccessful();
});

test('artisan velm db status exits successfully', function (): void {
    $this->artisan('velm:db:status')
        ->assertSuccessful();
});

test('artisan velm cron run exits successfully', function (): void {
    $this->artisan('velm:cron:run')
        ->assertSuccessful();
});

test('artisan velm migrate installs bootstrap modules', function (): void {
    config([
        'velm.addon_paths' => [dirname(__DIR__, 3).'/modules/modules'],
        'velm.bootstrap_modules' => ['base'],
    ]);

    $this->artisan('velm:migrate')
        ->assertSuccessful();
});

test('artisan velm migrate with module option installs partners', function (): void {
    config([
        'velm.addon_paths' => [dirname(__DIR__, 3).'/modules/modules'],
        'velm.bootstrap_modules' => ['base'],
    ]);

    $this->artisan('velm:migrate', ['--module' => 'partners'])
        ->assertSuccessful();
});

test('artisan velm migrate fresh with yes flag reinstalls modules', function (): void {
    config([
        'velm.addon_paths' => [dirname(__DIR__, 3).'/modules/modules'],
        'velm.bootstrap_modules' => ['base'],
    ]);

    $velm = app(\Velm\Framework\VelmManager::class);
    $velm->installBootstrap();
    $velm->install('partners');

    $this->artisan('velm:migrate:fresh', ['--yes' => true, '--module' => ['partners']])
        ->assertSuccessful();
});

test('artisan velm module install and sync partners', function (): void {
    config([
        'velm.addon_paths' => [dirname(__DIR__, 3).'/modules/modules'],
        'velm.bootstrap_modules' => ['base'],
    ]);

    $this->artisan('velm:module:install', ['module' => 'partners'])
        ->assertSuccessful();

    $this->artisan('velm:module:sync', ['module' => 'partners'])
        ->assertSuccessful();
});

test('artisan velm module uninstall rejects protected base module', function (): void {
    $this->artisan('velm:module:uninstall', ['module' => 'base'])
        ->assertFailed();
});

test('artisan velm migrate fresh aborts without yes flag', function (): void {
    config([
        'velm.addon_paths' => [dirname(__DIR__, 3).'/modules/modules'],
        'velm.bootstrap_modules' => ['base'],
    ]);

    $this->artisan('velm:migrate:fresh')
        ->expectsConfirmation('This will DROP Velm tables and reinstall modules. Continue?', false)
        ->assertSuccessful();
});

test('artisan velm module sync all exits successfully', function (): void {
    config([
        'velm.addon_paths' => [dirname(__DIR__, 3).'/modules/modules'],
        'velm.bootstrap_modules' => ['base'],
    ]);

    app(\Velm\Framework\VelmManager::class)->installBootstrap();
    app(\Velm\Framework\VelmManager::class)->install('partners');

    $this->artisan('velm:module:sync-all')
        ->assertSuccessful();
});

test('artisan velm db diff reports no drift for installed partners', function (): void {
    config([
        'velm.addon_paths' => [dirname(__DIR__, 3).'/modules/modules'],
        'velm.bootstrap_modules' => ['base'],
    ]);

    app(\Velm\Framework\VelmManager::class)->installBootstrap();
    app(\Velm\Framework\VelmManager::class)->install('partners');

    $this->artisan('velm:db:diff', ['--module' => 'partners'])
        ->assertSuccessful();
});

test('artisan velm db autogen dry run for partners', function (): void {
    config([
        'velm.addon_paths' => [dirname(__DIR__, 3).'/modules/modules'],
        'velm.bootstrap_modules' => ['base'],
    ]);

    app(\Velm\Framework\VelmManager::class)->installBootstrap();
    app(\Velm\Framework\VelmManager::class)->install('partners');

    $this->artisan('velm:db:autogen', ['--module' => 'partners', '--dry-run' => true])
        ->assertSuccessful();
});
