<?php

declare(strict_types=1);

use Velm\Database\PdoConnection;
use Velm\Environment;
use Velm\Modules\ModuleHookRunner;
use Velm\Modules\Tests\Support\InstallHookProbe;
use Velm\Modules\Tests\TestCase;
use Velm\Registry;

uses(TestCase::class);

test('module hook runner ignores empty hooks', function (): void {
    $runner = new ModuleHookRunner;
    $env = new Environment(PdoConnection::sqliteMemory(), new Registry);

    $runner->runInstallHook(null, $env);
    $runner->runSyncHook('', $env);

    expect(InstallHookProbe::$calls)->toBe(0);
});

test('module hook runner resolves install hook callable', function (): void {
    InstallHookProbe::$calls = 0;
    $runner = new ModuleHookRunner;
    $env = new Environment(PdoConnection::sqliteMemory(), new Registry);

    $runner->runInstallHook(InstallHookProbe::class.'::install', $env);

    expect(InstallHookProbe::$calls)->toBe(1);
});

test('module hook runner rejects invalid hook references', function (): void {
    $runner = new ModuleHookRunner;
    $env = new Environment(PdoConnection::sqliteMemory(), new Registry);

    expect(fn () => $runner->runInstallHook('NotAHook', $env))
        ->toThrow(InvalidArgumentException::class, 'INSTALL_HOOK');

    expect(fn () => $runner->runInstallHook('::missing', $env))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => $runner->runInstallHook('Missing\\Class::method', $env))
        ->toThrow(RuntimeException::class, 'class');

    expect(fn () => $runner->runInstallHook(InstallHookProbe::class.'::missing', $env))
        ->toThrow(RuntimeException::class, 'method');
});
