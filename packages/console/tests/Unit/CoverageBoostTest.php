<?php

declare(strict_types=1);

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Velm\Console\Commands\DbAutogenCommand;
use Velm\Console\Commands\MigrateCommand;
use Velm\Console\Commands\ModuleInstallCommand;
use Velm\Console\Commands\ModuleListCommand;
use Velm\Console\Commands\ModuleSyncCommand;
use Velm\Console\Commands\ModuleUninstallCommand;
use Velm\Console\Scaffold\ManifestPatcher;
use Velm\Console\Scaffold\MenuScaffolder;
use Velm\Console\Scaffold\ModelScaffolder;
use Velm\Console\Scaffold\ModulePathResolver;
use Velm\Console\Scaffold\ModuleScaffolder;
use Velm\Console\Scaffold\ViewScaffolder;
use Velm\Console\Tests\ConsoleTestCase;
use Velm\Console\Tests\Support\EmptyModuleRootsListCommand;
use Velm\Console\Tests\Support\FailingDbAutogenCommand;
use Velm\Console\Tests\Support\NoLaravelDbAutogenCommand;
use Velm\Console\Tests\Support\NoLaravelMigrateCommand;
use Velm\Console\Tests\Support\NoLaravelModuleInstallCommand;
use Velm\Console\Tests\Support\NoLaravelModuleListCommand;
use Velm\Console\Tests\Support\NoLaravelModuleSyncCommand;
use Velm\Console\Tests\Support\NoLaravelModuleUninstallCommand;

uses(ConsoleTestCase::class);

test('standalone module list discovered-only works when database unavailable message path is skipped', function (): void {
    $tester = $this->runCommand(new ModuleListCommand, ['--discovered-only' => true]);

    expect($tester->getStatusCode())->toBe(Command::SUCCESS)
        ->and($tester->getDisplay())->toContain('Module');
});

test('standalone migrate command installs specific module', function (): void {
    $tester = $this->runCommand(new MigrateCommand, ['--module' => 'partners']);

    expect($tester->getStatusCode())->toBe(Command::SUCCESS)
        ->and($tester->getDisplay())->toContain('Migrated partners');
});

test('standalone db autogen dry run prints migration body', function (): void {
    $this->runCommand(new MigrateCommand);
    $this->runCommand(new ModuleInstallCommand, ['module' => 'partners']);

    $tester = $this->runCommand(new DbAutogenCommand, [
        '--module' => 'partners',
        '--dry-run' => true,
    ]);

    expect($tester->getStatusCode())->toBe(Command::SUCCESS)
        ->and($tester->getDisplay())->toContain('Schema');
});

test('standalone module install and sync succeed for partners', function (): void {
    $install = $this->runCommand(new ModuleInstallCommand, ['module' => 'partners']);
    $sync = $this->runCommand(new ModuleSyncCommand, ['module' => 'partners']);

    expect($install->getStatusCode())->toBe(Command::SUCCESS)
        ->and($sync->getStatusCode())->toBe(Command::SUCCESS)
        ->and($install->getDisplay())->toContain('Installed partners');
});

test('standalone module uninstall fails when module missing', function (): void {
    $tester = $this->runCommand(new ModuleUninstallCommand, ['module' => 'missing_module_xyz']);

    expect($tester->getStatusCode())->toBe(Command::FAILURE);
});

test('menu scaffolder validates view name and append edge cases', function (): void {
    $root = sys_get_temp_dir().'/velm_menu_cov_'.uniqid('', true);
    mkdir($root, 0777, true);
    (new ModuleScaffolder)->scaffold('demo', $root);
    $modulePath = $root.'/demo';
    $scaffolder = new MenuScaffolder;

    expect(fn () => $scaffolder->scaffold('demo', $modulePath, ''))
        ->toThrow(InvalidArgumentException::class, '--view=');

    $scaffolder->scaffold('demo', $modulePath, 'alpha.list');
    $result = $scaffolder->scaffold('demo', $modulePath, 'alpha.list', append: true);

    expect($result['view'])->toBe('alpha.list');

    file_put_contents($modulePath.'/views/menu.php', "<?php\nreturn [];\n");

    expect(fn () => $scaffolder->scaffold('demo', $modulePath, 'beta.list', append: true))
        ->toThrow(RuntimeException::class, 'no ->menus() block');

    expect(fn () => $scaffolder->scaffold('demo', $modulePath, 'gamma.list'))
        ->toThrow(RuntimeException::class, 'already exists');

    @unlink($modulePath.'/views/menu.php');
    @rmdir($modulePath.'/views');
    @unlink($modulePath.'/models/.gitkeep');
    @rmdir($modulePath.'/models');
    @unlink($modulePath.'/migrations/.gitkeep');
    @rmdir($modulePath.'/migrations');
    @unlink($modulePath.'/__velm__.php');
    @rmdir($modulePath);
    @rmdir($root);
});

test('manifest patcher appends to existing inline data call', function (): void {
    $dir = sys_get_temp_dir().'/velm-patch-inline-data-'.uniqid('', true);
    mkdir($dir, 0777, true);
    $manifest = $dir.'/__velm__.php';

    file_put_contents($manifest, <<<'PHP'
<?php
declare(strict_types=1);

use Velm\Modules\Manifest;
return Manifest::make('patch_inline_data')
    ->version(0, 1, 0)
    ->depends('base')
    ->data('views/existing.php');
PHP);

    ManifestPatcher::appendData($manifest, 'views/new.php');

    expect(file_get_contents($manifest))->toContain('views/new.php');

    @unlink($manifest);
    @rmdir($dir);
});

test('manifest patcher appendModel adds to inline models list', function (): void {
    $dir = sys_get_temp_dir().'/velm-patch-inline-models-'.uniqid('', true);
    mkdir($dir, 0777, true);
    $manifest = $dir.'/__velm__.php';

    file_put_contents($manifest, <<<'PHP'
<?php
declare(strict_types=1);

use Velm\Modules\Manifest;
use App\Models\First;
return Manifest::make('patch_inline_models')
    ->version(0, 1, 0)
    ->depends('base')
    ->models(First::class);
PHP);

    ManifestPatcher::appendModel($manifest, 'App\\Models\\Second', 'Second');

    expect(file_get_contents($manifest))->toContain('Second::class');

    @unlink($manifest);
    @rmdir($dir);
});

test('manifest patcher read throws when manifest path is a directory', function (): void {
    $dir = sys_get_temp_dir().'/velm-patch-dir-'.uniqid('', true);
    mkdir($dir.'/__velm__.php', 0777, true);

    try {
        ManifestPatcher::appendData($dir.'/__velm__.php', 'views/x.php');
    } finally {
        @rmdir($dir.'/__velm__.php');
        @rmdir($dir);
    }
})->throws(RuntimeException::class);

test('model scaffolder validates empty and invalid names', function (): void {
    $root = sys_get_temp_dir().'/velm_model_cov_'.uniqid('', true);
    mkdir($root, 0777, true);
    (new ModuleScaffolder)->scaffold('demo', $root);
    $modulePath = $root.'/demo';
    $scaffolder = new ModelScaffolder;

    expect(fn () => $scaffolder->scaffold('', 'demo', $modulePath))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => $scaffolder->scaffold('Bad-Name', 'demo', $modulePath))
        ->toThrow(InvalidArgumentException::class);

    @unlink($modulePath.'/models/.gitkeep');
    @rmdir($modulePath.'/models');
    @unlink($modulePath.'/migrations/.gitkeep');
    @rmdir($modulePath.'/migrations');
    @unlink($modulePath.'/__velm__.php');
    @rmdir($modulePath);
    @rmdir($root);
});

test('view scaffolder force overwrite and registry resolution', function (): void {
    $root = sys_get_temp_dir().'/velm_view_cov_'.uniqid('', true);
    mkdir($root, 0777, true);
    (new ModuleScaffolder)->scaffold('sales', $root);
    $modulePath = $root.'/sales';
    $scaffolder = new ViewScaffolder;

    expect(fn () => $scaffolder->scaffold('sales.order', 'sales', $modulePath, fromModel: true))
        ->toThrow(InvalidArgumentException::class, 'Cannot introspect');

    $scaffolder->scaffold('sales.order', 'sales', $modulePath, fromModel: false);
    $scaffolder->scaffold('sales.order', 'sales', $modulePath, fromModel: false, force: true);

    expect(is_file($modulePath.'/views/order.php'))->toBeTrue()
        ->and($scaffolder->resolveTechnical('order', 'sales', null))->toBe('sales.order');

    expect(fn () => $scaffolder->normalizeForViews('', 'sales'))
        ->toThrow(InvalidArgumentException::class);

    @unlink($modulePath.'/views/order.php');
    @rmdir($modulePath.'/views');
    @unlink($modulePath.'/models/.gitkeep');
    @rmdir($modulePath.'/models');
    @unlink($modulePath.'/migrations/.gitkeep');
    @rmdir($modulePath.'/migrations');
    @unlink($modulePath.'/__velm__.php');
    @rmdir($modulePath);
    @rmdir($root);
});

test('module path resolver inferModuleFromCwd returns null when cwd unavailable', function (): void {
    expect(ModulePathResolver::inferModuleFromCwd(false))->toBeNull();
});

test('module path resolver resolveAddonRoot uses laravel addons directory when present', function (): void {
    config(['velm.addon_paths' => [base_path('vendor/example/modules')]]);

    expect(ModulePathResolver::resolveAddonRoot(null))->toBe(base_path('addons'));
});

test('standalone console commands fail when laravel database is unavailable', function (): void {
    $commands = [
        [new NoLaravelModuleInstallCommand, ['module' => 'partners'], 'module:install requires'],
        [new NoLaravelModuleSyncCommand, ['module' => 'partners'], 'module:sync requires'],
        [new NoLaravelMigrateCommand, [], 'migrate requires'],
        [new NoLaravelModuleUninstallCommand, ['module' => 'partners'], 'module:uninstall requires'],
        [new NoLaravelDbAutogenCommand, ['--module' => 'partners'], 'db:autogen requires'],
    ];

    foreach ($commands as [$command, $args, $needle]) {
        $tester = $this->runCommand($command, $args);

        expect($tester->getStatusCode())->toBe(Command::FAILURE)
            ->and($tester->getDisplay())->toContain($needle);
    }
});

test('standalone module list fails when addon paths are empty', function (): void {
    $tester = $this->runCommand(new EmptyModuleRootsListCommand);

    expect($tester->getStatusCode())->toBe(Command::FAILURE)
        ->and($tester->getDisplay())->toContain('No addon paths configured');
});

test('standalone module list shows discovered modules when database is unavailable', function (): void {
    $tester = $this->runCommand(new NoLaravelModuleListCommand);

    expect($tester->getStatusCode())->toBe(Command::SUCCESS)
        ->and($tester->getDisplay())->toContain('Laravel database not bootstrapped')
        ->and($tester->getDisplay())->toContain('base');
});

test('standalone db autogen surfaces installer failures', function (): void {
    $tester = $this->runCommand(new FailingDbAutogenCommand, [
        '--module' => 'partners',
        '--dry-run' => true,
    ]);

    expect($tester->getStatusCode())->toBe(Command::FAILURE)
        ->and($tester->getDisplay())->toContain('Autogen failed in test');
});

test('module path resolver resolveAddonRoot uses explicit addon root path', function (): void {
    $custom = sys_get_temp_dir().'/velm-custom-addons-'.uniqid('', true);
    mkdir($custom, 0777, true);

    expect(ModulePathResolver::resolveAddonRoot($custom))->toBe($custom);

    @rmdir($custom);
});

test('view scaffold builder fallback list uses id when no scalar fields', function (): void {
    $registry = new Velm\Registry;
    $registry->register(\Velm\Core\Tests\Support\EmptyFieldsModel::class);

    $built = (new Velm\Console\Scaffold\ViewScaffoldBuilder)->build($registry, 'test.empty', 1);

    expect($built['list'])->toBe(["'id'"])
        ->and($built['sections'][0]['fields'])->toBe(["'id'"]);
});
