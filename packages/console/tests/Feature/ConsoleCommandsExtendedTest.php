<?php

declare(strict_types=1);

use Symfony\Component\Console\Command\Command;
use Velm\Console\Commands\DbAutogenCommand;
use Velm\Console\Commands\DbDiffCommand;
use Velm\Console\Commands\DbStatusCommand;
use Velm\Console\Commands\MigrateCommand;
use Velm\Console\Commands\ModuleInstallCommand;
use Velm\Console\Commands\ModuleListCommand;
use Velm\Console\Commands\ModuleSyncCommand;
use Velm\Console\Commands\ModuleUninstallCommand;
use Velm\Console\Tests\ConsoleTestCase;
use Velm\Console\Tests\Support\ResPartnerSchemaHelper;

uses(ConsoleTestCase::class);

test('standalone migrate command installs bootstrap modules without module option', function (): void {
    $tester = $this->runCommand(new MigrateCommand);

    expect($tester->getStatusCode())->toBe(Command::SUCCESS)
        ->and($tester->getDisplay())->toContain('bootstrap');
});

test('standalone migrate command fails for unknown module', function (): void {
    $tester = $this->runCommand(new MigrateCommand, ['--module' => 'missing_module_xyz']);

    expect($tester->getStatusCode())->toBe(Command::FAILURE);
});

test('standalone db status command prints installed module table', function (): void {
    $this->runCommand(new MigrateCommand);
    $this->runCommand(new ModuleInstallCommand, ['module' => 'partners']);

    $tester = $this->runCommand(new DbStatusCommand);

    expect($tester->getStatusCode())->toBe(Command::SUCCESS)
        ->and($tester->getDisplay())->toContain('partners')
        ->and($tester->getDisplay())->toContain('Module');
});

test('standalone db status command warns when no modules installed', function (): void {
    $tester = $this->runCommand(new DbStatusCommand);

    expect($tester->getStatusCode())->toBe(Command::SUCCESS)
        ->and($tester->getDisplay())->toContain('No installed modules');
});

test('standalone db diff command reports no drift when schema matches', function (): void {
    $this->runCommand(new MigrateCommand);
    $this->runCommand(new ModuleInstallCommand, ['module' => 'partners']);

    $tester = $this->runCommand(new DbDiffCommand, ['--module' => 'partners']);

    expect($tester->getStatusCode())->toBe(Command::SUCCESS)
        ->and($tester->getDisplay())->toContain('No schema drift');
});

test('standalone db diff command reports new table drift', function (): void {
    $this->runCommand(new MigrateCommand);
    $this->runCommand(new ModuleInstallCommand, ['module' => 'partners']);
    \Illuminate\Support\Facades\DB::statement('DROP TABLE "res_partner"');

    $tester = $this->runCommand(new DbDiffCommand, ['--module' => 'partners']);

    expect($tester->getStatusCode())->toBe(Command::SUCCESS)
        ->and($tester->getDisplay())->toContain('+ table res_partner');
});

test('standalone db diff command reports new column drift', function (): void {
    $this->runCommand(new MigrateCommand);
    $this->runCommand(new ModuleInstallCommand, ['module' => 'partners']);
    \Illuminate\Support\Facades\DB::statement('ALTER TABLE "res_partner" DROP COLUMN "is_company"');

    $tester = $this->runCommand(new DbDiffCommand, ['--module' => 'partners']);

    expect($tester->getStatusCode())->toBe(Command::SUCCESS)
        ->and($tester->getDisplay())->toContain('+ column res_partner.is_company');
});

test('standalone db diff command reports set_not_null with null rows', function (): void {
    $this->runCommand(new MigrateCommand);
    $this->runCommand(new ModuleInstallCommand, ['module' => 'partners']);
    ResPartnerSchemaHelper::recreateWithNullableName(withNullNameRow: true);

    $tester = $this->runCommand(new DbDiffCommand, ['--module' => 'partners']);

    expect($tester->getStatusCode())->toBe(Command::SUCCESS)
        ->and($tester->getDisplay())->toContain('set_not_null')
        ->and($tester->getDisplay())->toContain('NULL row(s)');
});

test('standalone db diff command reports set_not_null with no null rows', function (): void {
    $this->runCommand(new MigrateCommand);
    $this->runCommand(new ModuleInstallCommand, ['module' => 'partners']);
    ResPartnerSchemaHelper::recreateWithNullableName(withNullNameRow: false);

    $tester = $this->runCommand(new DbDiffCommand, ['--module' => 'partners']);

    expect($tester->getStatusCode())->toBe(Command::SUCCESS)
        ->and($tester->getDisplay())->toContain('set_not_null')
        ->and($tester->getDisplay())->toContain('no NULL rows');
});

test('standalone db diff command fails for unknown module', function (): void {
    $tester = $this->runCommand(new DbDiffCommand, ['--module' => 'missing_module_xyz']);

    expect($tester->getStatusCode())->toBe(Command::FAILURE);
});

test('standalone module list command renders catalog with database', function (): void {
    $this->runCommand(new MigrateCommand);
    $this->runCommand(new ModuleInstallCommand, ['module' => 'partners']);

    $tester = $this->runCommand(new ModuleListCommand);

    expect($tester->getStatusCode())->toBe(Command::SUCCESS)
        ->and($tester->getDisplay())->toContain('partners')
        ->and($tester->getDisplay())->toContain('State');
});

test('standalone module uninstall command prints blockers for protected base module', function (): void {
    $this->runCommand(new MigrateCommand);
    $tester = $this->runCommand(new ModuleUninstallCommand, ['module' => 'base']);

    expect($tester->getStatusCode())->toBe(Command::FAILURE)
        ->and($tester->getDisplay())->toContain('protected system module');
});

test('standalone module uninstall command uninstalls partners', function (): void {
    $this->runCommand(new MigrateCommand);
    $this->runCommand(new ModuleInstallCommand, ['module' => 'partners']);

    $tester = $this->runCommand(new ModuleUninstallCommand, ['module' => 'partners']);

    expect($tester->getStatusCode())->toBe(Command::SUCCESS)
        ->and($tester->getDisplay())->toContain('Uninstalled partners');
});

test('standalone module install command fails for unknown module', function (): void {
    $tester = $this->runCommand(new ModuleInstallCommand, ['module' => 'missing_module_xyz']);

    expect($tester->getStatusCode())->toBe(Command::FAILURE);
});

test('standalone module sync command fails for unknown module', function (): void {
    $tester = $this->runCommand(new ModuleSyncCommand, ['module' => 'missing_module_xyz']);

    expect($tester->getStatusCode())->toBe(Command::FAILURE);
});

test('standalone db autogen command fails without module option', function (): void {
    $tester = $this->runCommand(new DbAutogenCommand, []);

    expect($tester->getStatusCode())->toBe(Command::FAILURE)
        ->and($tester->getDisplay())->toContain('--module');
});

test('standalone db autogen command fails for unknown module', function (): void {
    $tester = $this->runCommand(new DbAutogenCommand, ['--module' => 'missing_module_xyz']);

    expect($tester->getStatusCode())->toBe(Command::FAILURE)
        ->and($tester->getDisplay())->toContain('not discovered');
});

test('standalone db autogen command writes migration and bumps version', function (): void {
    $root = sys_get_temp_dir().'/velm-autogen-'.uniqid('', true);
    $modulePath = $root.'/autogen_demo';
    mkdir($modulePath.'/migrations', 0777, true);

    file_put_contents(
        $modulePath.'/__velm__.php',
        "<?php\n\ndeclare(strict_types=1);\n\nuse Velm\\Modules\\Manifest;\n\nreturn Manifest::make('autogen_demo')\n"
        ."    ->version(0, 1, 0)\n"
        ."    ->depends('base')\n"
        ."    ->summary('Autogen demo');\n",
    );

    config([
        'velm.addon_paths' => [
            dirname(__DIR__, 3).'/modules/modules',
            $root,
        ],
    ]);

    $this->runCommand(new MigrateCommand);
    $this->runCommand(new ModuleInstallCommand, ['module' => 'autogen_demo']);

    $tester = $this->runCommand(new DbAutogenCommand, [
        '--module' => 'autogen_demo',
        '--target-version' => '0.2.0',
    ]);

    expect($tester->getStatusCode())->toBe(Command::SUCCESS)
        ->and($tester->getDisplay())->toContain('Wrote')
        ->and($tester->getDisplay())->toContain('Bumped VERSION')
        ->and($tester->getDisplay())->toContain('no-op');

    $manifest = file_get_contents($modulePath.'/__velm__.php');

    expect($manifest)->toContain('0, 2, 0');
});
