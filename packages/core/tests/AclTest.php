<?php

declare(strict_types=1);

use Velm\Core\Tests\Support\SecuredPartner;
use Velm\Core\Tests\Support\SecurityAccess;
use Velm\Core\Tests\Support\SecurityGroup;
use Velm\Core\Tests\Support\SecurityUser;
use Velm\Database\PdoConnection;
use Velm\Environment;
use Velm\Exception\AccessDeniedException;
use Velm\Registry;
use Velm\Schema\SchemaBuilder;

function aclEnvironment(int $uid): Environment
{
    return Registry::using(function (Registry $registry) use ($uid): Environment {
        $registry->register(SecurityGroup::class);
        $registry->register(SecurityUser::class);
        $registry->register(SecurityAccess::class);
        $registry->register(SecuredPartner::class);

        $connection = PdoConnection::sqliteMemory();
        $schema = new SchemaBuilder($connection);
        $schema->syncRegistry($registry);

        return new Environment($connection, $registry, $uid);
    });
}

function seedAclBaseline(Environment $env): array
{
    return $env->withAclBypass(function () use ($env): array {
        $env->model('res.users')->create(['name' => 'Administrator', 'login' => 'admin']);
        $sales = $env->model('res.groups')->create(['name' => 'Sales']);
        $bob = $env->model('res.users')->create([
            'name' => 'Bob',
            'login' => 'bob',
            'group_ids' => $sales->ids(),
        ]);
        $env->model('res.partner')->create(['name' => 'Acme']);

        return ['sales' => $sales, 'bob_id' => $bob->ids()[0]];
    });
}

function aclEnvironmentForBob(): Environment
{
    $super = aclEnvironment(Environment::SUPERUSER_ID);
    $baseline = seedAclBaseline($super);

    return new Environment(
        $super->connection,
        $super->registry,
        $baseline['bob_id'],
        $super->context,
        $super->cache,
    );
}

test('superuser bypasses ir.model.access checks', function (): void {
    $env = aclEnvironment(Environment::SUPERUSER_ID);
    seedAclBaseline($env);

    expect($env->model('res.partner')->search()->count())->toBe(1);
});

test('user without grants cannot read partners', function (): void {
    $env = aclEnvironmentForBob();

    expect(fn () => $env->model('res.partner')->search())
        ->toThrow(AccessDeniedException::class, 'read');
});

test('group grant allows read but not write', function (): void {
    $env = aclEnvironmentForBob();
    $baseline = ['sales' => $env->withAclBypass(fn () => $env->model('res.groups')->search([['name', '=', 'Sales']]))];

    $env->withAclBypass(fn () => $env->model('ir.model.access')->create([
        'name' => 'Sales/partner read',
        'model' => 'res.partner',
        'group_id' => $baseline['sales']->ids()[0],
        'perm_read' => true,
    ]));

    expect($env->model('res.partner')->search()->count())->toBe(1);

    expect(fn () => $env->model('res.partner')->search()->write(['name' => 'Denied']))
        ->toThrow(AccessDeniedException::class, 'write');
});

test('global grant applies to any authenticated user', function (): void {
    $env = aclEnvironmentForBob();

    $env->withAclBypass(fn () => $env->model('ir.model.access')->create([
        'name' => 'Everyone/partner read',
        'model' => 'res.partner',
        'group_id' => null,
        'perm_read' => true,
    ]));

    expect($env->model('res.partner')->search()->count())->toBe(1);
});

test('many2many group membership resolves for access checks', function (): void {
    $super = aclEnvironment(Environment::SUPERUSER_ID);
    $baseline = seedAclBaseline($super);
    $env = new Environment(
        $super->connection,
        $super->registry,
        $baseline['bob_id'],
        $super->context,
        $super->cache,
    );

    expect($env->userGroupIds())->toBe($baseline['sales']->ids());

    $env->withAclBypass(fn () => $env->model('ir.model.access')->create([
        'name' => 'Sales/partner create',
        'model' => 'res.partner',
        'group_id' => $baseline['sales']->ids()[0],
        'perm_create' => true,
    ]));

    $created = $env->model('res.partner')->create(['name' => 'New Co']);

    expect($created->ids())->toHaveCount(1);
});

test('when ir.model.access is absent all operations are allowed', function (): void {
    $env = Registry::using(function (Registry $registry): Environment {
        $registry->register(SecuredPartner::class);
        $connection = PdoConnection::sqliteMemory();
        (new SchemaBuilder($connection))->syncRegistry($registry);

        return new Environment($connection, $registry, 99);
    });

    $env->model('res.partner')->create(['name' => 'Open']);

    expect($env->model('res.partner')->search()->count())->toBe(1);
});
