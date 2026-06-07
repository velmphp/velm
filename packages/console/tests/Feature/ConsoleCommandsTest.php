<?php

declare(strict_types=1);

use Symfony\Component\Console\Command\Command;
use Velm\Console\Commands\DbDiffCommand;
use Velm\Console\Commands\DbStatusCommand;
use Velm\Console\Commands\ListCommand;
use Velm\Console\Commands\MigrateCommand;
use Velm\Console\Commands\ModuleInstallCommand;
use Velm\Console\Commands\ModuleListCommand;
use Velm\Console\Commands\ModuleSyncCommand;
use Velm\Console\Commands\ModuleUninstallCommand;
use Velm\Console\Tests\ConsoleTestCase;

uses(ConsoleTestCase::class);

test('module list discovered-only renders bundled modules', function (): void {
    $tester = $this->runCommand(new ModuleListCommand, ['--discovered-only' => true]);

    expect($tester->getStatusCode())->toBe(Command::SUCCESS)
        ->and($tester->getDisplay())->toContain('base')
        ->and($tester->getDisplay())->toContain('partners');
});

test('velm list command prints artisan help', function (): void {
    $tester = $this->runCommand(new ListCommand);

    expect($tester->getStatusCode())->toBe(Command::SUCCESS)
        ->and($tester->getDisplay())->toContain('velm:migrate');
});

test('standalone migrate command succeeds with testbench database', function (): void {
    $tester = $this->runCommand(new MigrateCommand);

    expect($tester->getStatusCode())->toBe(Command::SUCCESS);
});

test('standalone db status command succeeds with testbench database', function (): void {
    $tester = $this->runCommand(new DbStatusCommand);

    expect($tester->getStatusCode())->toBe(Command::SUCCESS);
});

test('standalone db diff command accepts module option', function (): void {
    $this->runCommand(new MigrateCommand);
    $this->runCommand(new ModuleInstallCommand, ['module' => 'partners']);
    $tester = $this->runCommand(new DbDiffCommand, ['--module' => 'partners']);

    expect($tester->getStatusCode())->toBe(Command::SUCCESS);
});

test('standalone module install command installs partners', function (): void {
    $tester = $this->runCommand(new ModuleInstallCommand, ['module' => 'partners']);

    expect($tester->getStatusCode())->toBe(Command::SUCCESS);
});

test('standalone module sync command syncs partners', function (): void {
    $this->runCommand(new ModuleInstallCommand, ['module' => 'partners']);
    $tester = $this->runCommand(new ModuleSyncCommand, ['module' => 'partners']);

    expect($tester->getStatusCode())->toBe(Command::SUCCESS);
});

test('standalone module uninstall command fails for protected base module', function (): void {
    $tester = $this->runCommand(new ModuleUninstallCommand, ['module' => 'base']);

    expect($tester->getStatusCode())->toBe(Command::FAILURE);
});
