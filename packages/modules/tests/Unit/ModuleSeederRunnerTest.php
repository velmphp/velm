<?php

declare(strict_types=1);

use Velm\Database\PdoConnection;
use Velm\Environment;
use Velm\Modules\ModuleDiscovery;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\ModuleRepository;
use Velm\Modules\Seeding\ModuleSeederRunner;
use Velm\Modules\Tests\Support\ModuleRoots;
use Velm\Modules\Tests\Support\ProbeSeeder;
use Velm\Modules\Tests\TestCase;
use Velm\Registry;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

function seederFixtureRoot(string $suffix = ''): string
{
    $root = sys_get_temp_dir().'/velm_seeder_'.$suffix.uniqid('', true);
    mkdir($root.'/probe', 0777, true);

    return $root;
}

test('module seeder runner invokes installed module seeders', function (): void {
    $root = seederFixtureRoot();
    file_put_contents($root.'/probe/__velm__.php', <<<'PHP'
<?php

declare(strict_types=1);

use Velm\Modules\Manifest;

return Manifest::make('probe')->version(0, 1, 0)->seeders(\Velm\Modules\Tests\Support\ProbeSeeder::class);
PHP);

    ProbeSeeder::$ran = [];
    $spec = (new ModuleDiscovery)->discover([$root])['probe'];
    $env = new Environment(PdoConnection::sqliteMemory(), new Registry);

    (new ModuleRepository)->markInstalled($spec);
    (new ModuleSeederRunner)->run($env, [$root]);

    expect(ProbeSeeder::$ran)->toBe(['probe']);

    @unlink($root.'/probe/__velm__.php');
    @rmdir($root.'/probe');
    @rmdir($root);
});

test('module seeder runner filters to requested module closure', function (): void {
    $root = seederFixtureRoot('filter_');
    file_put_contents($root.'/probe/__velm__.php', <<<'PHP'
<?php

declare(strict_types=1);

use Velm\Modules\Manifest;

return Manifest::make('probe')->version(0, 1, 0)->seeders(\Velm\Modules\Tests\Support\ProbeSeeder::class);
PHP);

    ProbeSeeder::$ran = [];
    $spec = (new ModuleDiscovery)->discover([$root])['probe'];
    $env = new Environment(PdoConnection::sqliteMemory(), new Registry);

    (new ModuleRepository)->markInstalled($spec);
    (new ModuleSeederRunner)->run($env, [$root], 'probe');

    expect(ProbeSeeder::$ran)->toBe(['probe']);

    @unlink($root.'/probe/__velm__.php');
    @rmdir($root.'/probe');
    @rmdir($root);
});

test('module seeder runner rejects missing seeder class', function (): void {
    $root = seederFixtureRoot('missing_');
    file_put_contents($root.'/probe/__velm__.php', <<<'PHP'
<?php

declare(strict_types=1);

use Velm\Modules\Manifest;

return Manifest::make('probe')->version(0, 1, 0)->seeders('Missing\\SeederClass');
PHP);

    $spec = (new ModuleDiscovery)->discover([$root])['probe'];
    $env = new Environment(PdoConnection::sqliteMemory(), new Registry);

    (new ModuleRepository)->markInstalled($spec);

    expect(fn () => (new ModuleSeederRunner)->run($env, [$root]))
        ->toThrow(RuntimeException::class, 'was not found');

    @unlink($root.'/probe/__velm__.php');
    @rmdir($root.'/probe');
    @rmdir($root);
});

test('module seeder runner rejects seeder without run method', function (): void {
    $root = seederFixtureRoot('norun_');
    file_put_contents($root.'/probe/__velm__.php', <<<'PHP'
<?php

declare(strict_types=1);

use Velm\Modules\Manifest;

return Manifest::make('probe')->version(0, 1, 0)->seeders(BadSeeder::class);
PHP);
    file_put_contents($root.'/probe/BadSeeder.php', <<<'PHP'
<?php
final class BadSeeder {}
PHP);
    require_once $root.'/probe/BadSeeder.php';

    $spec = (new ModuleDiscovery)->discover([$root])['probe'];
    $env = new Environment(PdoConnection::sqliteMemory(), new Registry);

    (new ModuleRepository)->markInstalled($spec);

    expect(fn () => (new ModuleSeederRunner)->run($env, [$root]))
        ->toThrow(RuntimeException::class, 'must define a static run');

    @unlink($root.'/probe/BadSeeder.php');
    @unlink($root.'/probe/__velm__.php');
    @rmdir($root.'/probe');
    @rmdir($root);
});

test('module seeder runner rejects undiscovered module filter', function (): void {
    $env = new Environment(PdoConnection::sqliteMemory(), new Registry);

    expect(fn () => (new ModuleSeederRunner)->run($env, [], 'missing'))
        ->toThrow(InvalidArgumentException::class, 'was not discovered');
});

test('module seeder runner runs geo reference seeder for installed geo_data module', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base']);
    $installer->install('geo_data', $roots);

    $env = $installer->environment($roots);

    (new ModuleSeederRunner)->run($env, $roots, 'geo_data');

    expect($env->model('res.country')->search([], limit: 1)->count())->toBeGreaterThan(0);
});
