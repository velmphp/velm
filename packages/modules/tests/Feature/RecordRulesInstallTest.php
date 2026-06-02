<?php

declare(strict_types=1);

use Velm\Environment;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\Tests\TestCase;
use Velm\Web\Api\RecordQuery;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('record rule filters partners for limited user via api search path', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'partners']);

    $env = $installer->environment($roots);
    $env->withAclBypass(function () use ($env): void {
        $env->model('res.partner')->create(['name' => 'Visible Co']);
        $env->model('res.partner')->create(['name' => 'Hidden Co']);
        $sales = $env->model('res.groups')->create(['name' => 'Sales']);
        $env->model('res.users')->create([
            'name' => 'Limited',
            'login' => 'limited',
            'group_ids' => $sales->ids(),
        ]);
        $env->model('ir.model.access')->create([
            'name' => 'Sales/partner read',
            'model' => 'res.partner',
            'group_id' => $sales->ids()[0],
            'perm_read' => true,
        ]);
        $env->model('ir.rule')->create([
            'name' => 'Visible only',
            'model' => 'res.partner',
            'group_id' => $sales->ids()[0],
            'perm_read' => true,
            'domain' => json_encode([['name', '=', 'Visible Co']]),
        ]);
    });

    $limited = $env->model('res.users')->search([['login', '=', 'limited']]);
    $limitedEnv = new Environment(
        $env->connection,
        $env->registry,
        uid: $limited->ids()[0],
    );

    $query = new RecordQuery;
    $payload = $query->list($limitedEnv, 'res.partner');

    expect($payload['count'])->toBe(1)
        ->and($payload['records'][0]['name'])->toBe('Visible Co');
});

test('base install registers ir.rule model', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base']);

    $env = $installer->environment($roots);

    expect($env->registry->has('ir.rule'))->toBeTrue();
});

test('global and group rules both constrain search for member', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'partners']);

    $env = $installer->environment($roots);
    $baseline = $env->withAclBypass(function () use ($env): array {
        $env->model('res.partner')->create(['name' => 'Match', 'active' => true]);
        $env->model('res.partner')->create(['name' => 'Match', 'active' => false]);
        $env->model('res.partner')->create(['name' => 'Other', 'active' => true]);
        $sales = $env->model('res.groups')->create(['name' => 'Sales']);
        $env->model('res.users')->create(['name' => 'Administrator', 'login' => 'admin']);
        $bob = $env->model('res.users')->create([
            'name' => 'Bob',
            'login' => 'bob',
            'group_ids' => $sales->ids(),
        ]);
        $env->model('ir.model.access')->create([
            'name' => 'Sales/partner read',
            'model' => 'res.partner',
            'group_id' => $sales->ids()[0],
            'perm_read' => true,
        ]);
        $env->model('ir.rule')->create([
            'name' => 'Name match',
            'model' => 'res.partner',
            'group_id' => $sales->ids()[0],
            'perm_read' => true,
            'domain' => json_encode([['name', '=', 'Match']]),
        ]);
        $env->model('ir.rule')->create([
            'name' => 'Active',
            'model' => 'res.partner',
            'group_id' => null,
            'perm_read' => true,
            'domain' => json_encode([['active', '=', true]]),
        ]);

        return ['bob_id' => $bob->ids()[0]];
    });

    $bobEnv = new Environment($env->connection, $env->registry, $baseline['bob_id']);

    expect($bobEnv->model('res.partner')->search()->count())->toBe(1)
        ->and($bobEnv->model('res.partner')->search()->read()[0]['active'])->toBeTrue();
});

test('m2o search api respects record rules', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'partners']);

    $env = $installer->environment($roots);
    $env->withAclBypass(function () use ($env): void {
        $env->model('res.partner')->create(['name' => 'Allowed Partner']);
        $env->model('res.partner')->create(['name' => 'Blocked Partner']);
        $env->model('res.users')->create(['name' => 'Limited', 'login' => 'limited']);
        $env->model('ir.model.access')->create([
            'name' => 'All/partner read',
            'model' => 'res.partner',
            'group_id' => null,
            'perm_read' => true,
        ]);
        $env->model('ir.rule')->create([
            'name' => 'Allowed only',
            'model' => 'res.partner',
            'group_id' => null,
            'perm_read' => true,
            'domain' => json_encode([['name', 'ilike', '%Allowed%']]),
        ]);
    });

    $limited = $env->model('res.users')->search([['login', '=', 'limited']]);
    $limitedEnv = new Environment($env->connection, $env->registry, uid: $limited->ids()[0]);

    $payload = (new \Velm\Web\Api\Many2oneSearch)->search($limitedEnv, 'res.partner', '', 10);

    expect($payload['results'])->toHaveCount(1)
        ->and($payload['results'][0]['label'])->toBe('Allowed Partner');
});

test('record query patch returns not found for rule hidden id', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'partners']);

    $env = $installer->environment($roots);
    $hiddenId = $env->withAclBypass(function () use ($env): int {
        $env->model('res.partner')->create(['name' => 'Visible']);
        $hidden = $env->model('res.partner')->create(['name' => 'Hidden']);
        $env->model('ir.model.access')->create([
            'name' => 'All/partner read',
            'model' => 'res.partner',
            'group_id' => null,
            'perm_read' => true,
        ]);
        $env->model('ir.rule')->create([
            'name' => 'Visible only',
            'model' => 'res.partner',
            'group_id' => null,
            'perm_read' => true,
            'domain' => json_encode([['name', '=', 'Visible']]),
        ]);

        return $hidden->ids()[0];
    });

    $limitedEnv = new Environment($env->connection, $env->registry, uid: 2);
    $query = new RecordQuery;

    expect(fn () => $query->write($limitedEnv, 'res.partner', $hiddenId, ['name' => 'Hack']))
        ->toThrow(\Velm\Web\Api\RecordNotFoundException::class);
});
