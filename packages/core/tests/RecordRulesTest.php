<?php

declare(strict_types=1);

use Velm\Core\Tests\Support\SecuredPartner;
use Velm\Core\Tests\Support\SecurityAccess;
use Velm\Core\Tests\Support\SecurityGroup;
use Velm\Core\Tests\Support\SecurityRule;
use Velm\Core\Tests\Support\SecurityUser;
use Velm\Database\PdoConnection;
use Velm\Environment;
use Velm\Exception\AccessDeniedException;
use Velm\Registry;
use Velm\Schema\SchemaBuilder;

function recordRulesRegistry(): Registry
{
    return Registry::using(function (Registry $registry): Registry {
        $registry->register(SecurityGroup::class);
        $registry->register(SecurityUser::class);
        $registry->register(SecurityAccess::class);
        $registry->register(SecurityRule::class);
        $registry->register(SecuredPartner::class);

        return $registry;
    });
}

function recordRulesSuperEnv(): Environment
{
    $registry = recordRulesRegistry();
    $connection = PdoConnection::sqliteMemory();
    (new SchemaBuilder($connection))->syncRegistry($registry);

    return new Environment($connection, $registry, Environment::SUPERUSER_ID);
}

test('record rules without ir.rule model do not filter search', function (): void {
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(SecuredPartner::class);

        return $registry;
    });
    $connection = PdoConnection::sqliteMemory();
    (new SchemaBuilder($connection))->syncRegistry($registry);
    $env = new Environment($connection, $registry, 99);
    $env->model('res.partner')->create(['name' => 'Open']);

    expect($env->collectRecordRules('res.partner', 'read'))->toBe([])
        ->and($env->model('res.partner')->search()->count())->toBe(1);
});

test('global rule filters rows on search', function (): void {
    $super = recordRulesSuperEnv();
    $super->withAclBypass(function () use ($super): void {
        $super->model('res.partner')->create(['name' => 'Public']);
        $super->model('res.partner')->create(['name' => 'Secret']);
        $super->model('ir.model.access')->create([
            'name' => 'All/partner read',
            'model' => 'res.partner',
            'group_id' => null,
            'perm_read' => true,
        ]);
        $super->model('ir.rule')->create([
            'name' => 'Only public names',
            'model' => 'res.partner',
            'group_id' => null,
            'perm_read' => true,
            'domain' => json_encode([['name', '=', 'Public']]),
        ]);
    });

    $env = new Environment($super->connection, $super->registry, 2);

    expect($env->model('res.partner')->search()->count())->toBe(1)
        ->and($env->model('res.partner')->search()->read()[0]['name'])->toBe('Public');
});

test('superuser ignores record rules', function (): void {
    $super = recordRulesSuperEnv();
    $super->withAclBypass(function () use ($super): void {
        $super->model('res.partner')->create(['name' => 'Public']);
        $super->model('res.partner')->create(['name' => 'Secret']);
        $super->model('ir.rule')->create([
            'name' => 'Only public',
            'model' => 'res.partner',
            'group_id' => null,
            'perm_read' => true,
            'domain' => json_encode([['name', '=', 'Public']]),
        ]);
    });

    expect($super->model('res.partner')->search()->count())->toBe(2);
});

test('group-scoped rule applies only to members', function (): void {
    $super = recordRulesSuperEnv();
    $baseline = $super->withAclBypass(function () use ($super): array {
        $sales = $super->model('res.groups')->create(['name' => 'Sales']);
        $super->model('res.users')->create(['name' => 'Administrator', 'login' => 'admin']);
        $bob = $super->model('res.users')->create([
            'name' => 'Bob',
            'login' => 'bob',
            'group_ids' => $sales->ids(),
        ]);
        $super->model('res.partner')->create(['name' => 'Mine']);
        $super->model('res.partner')->create(['name' => 'Other']);
        $super->model('ir.model.access')->create([
            'name' => 'Sales/partner read',
            'model' => 'res.partner',
            'group_id' => $sales->ids()[0],
            'perm_read' => true,
        ]);
        $super->model('ir.rule')->create([
            'name' => 'Sales see Mine only',
            'model' => 'res.partner',
            'group_id' => $sales->ids()[0],
            'perm_read' => true,
            'domain' => json_encode([['name', '=', 'Mine']]),
        ]);

        return ['bob_id' => $bob->ids()[0]];
    });

    $bobEnv = new Environment($super->connection, $super->registry, $baseline['bob_id']);

    expect($bobEnv->model('res.partner')->search()->count())->toBe(1)
        ->and($bobEnv->model('res.partner')->search()->read()[0]['name'])->toBe('Mine');
});

test('placeholder uid resolves in rule domain', function (): void {
    $super = recordRulesSuperEnv();
    $bobId = $super->withAclBypass(function () use ($super): int {
        $super->model('res.users')->create(['name' => 'Administrator', 'login' => 'admin']);
        $bob = $super->model('res.users')->create(['name' => 'Bob', 'login' => 'bob']);
        $super->model('res.partner')->create(['name' => 'Bob row', 'owner_id' => $bob->ids()[0]]);
        $super->model('res.partner')->create(['name' => 'Other row', 'owner_id' => 99]);
        $super->model('ir.model.access')->create([
            'name' => 'All/partner read',
            'model' => 'res.partner',
            'group_id' => null,
            'perm_read' => true,
        ]);
        $super->model('ir.rule')->create([
            'name' => 'Own rows',
            'model' => 'res.partner',
            'group_id' => null,
            'perm_read' => true,
            'domain' => json_encode([['owner_id', '=', ['placeholder' => 'uid']]]),
        ]);

        return $bob->ids()[0];
    });

    $env = new Environment($super->connection, $super->registry, $bobId);

    expect($env->model('res.partner')->search()->count())->toBe(1)
        ->and($env->model('res.partner')->search()->read()[0]['name'])->toBe('Bob row');
});

test('collectRecordRules caches per model and perm', function (): void {
    $super = recordRulesSuperEnv();
    $super->withAclBypass(fn () => $super->model('ir.rule')->create([
        'name' => 'Rule',
        'model' => 'res.partner',
        'group_id' => null,
        'perm_read' => true,
        'domain' => json_encode([['name', '=', 'X']]),
    ]));

    $env = new Environment($super->connection, $super->registry, 2);
    $first = $env->collectRecordRules('res.partner', 'read');
    $second = $env->collectRecordRules('res.partner', 'read');

    expect($first)->toBe($second)
        ->and($first)->toHaveCount(1);
});

test('multiple rules AND all leaves into search', function (): void {
    $super = recordRulesSuperEnv();
    $super->withAclBypass(function () use ($super): void {
        $super->model('res.partner')->create(['name' => 'Public', 'active' => true]);
        $super->model('res.partner')->create(['name' => 'Public', 'active' => false]);
        $super->model('res.partner')->create(['name' => 'Secret', 'active' => true]);
        $super->model('ir.model.access')->create([
            'name' => 'All/partner read',
            'model' => 'res.partner',
            'group_id' => null,
            'perm_read' => true,
        ]);
        $super->model('ir.rule')->create([
            'name' => 'Name public',
            'model' => 'res.partner',
            'group_id' => null,
            'perm_read' => true,
            'domain' => json_encode([['name', '=', 'Public']]),
        ]);
        $super->model('ir.rule')->create([
            'name' => 'Active only',
            'model' => 'res.partner',
            'group_id' => null,
            'perm_read' => true,
            'domain' => json_encode([['active', '=', true]]),
        ]);
    });

    $env = new Environment($super->connection, $super->registry, 2);

    expect($env->collectRecordRules('res.partner', 'read'))->toHaveCount(2)
        ->and($env->model('res.partner')->search()->count())->toBe(1)
        ->and($env->model('res.partner')->search()->read()[0]['name'])->toBe('Public');
});

test('user domain combines with record rules', function (): void {
    $super = recordRulesSuperEnv();
    $super->withAclBypass(function () use ($super): void {
        $super->model('res.partner')->create(['name' => 'Public', 'active' => true]);
        $super->model('res.partner')->create(['name' => 'Public', 'active' => false]);
        $super->model('ir.model.access')->create([
            'name' => 'All/partner read',
            'model' => 'res.partner',
            'group_id' => null,
            'perm_read' => true,
        ]);
        $super->model('ir.rule')->create([
            'name' => 'Name public',
            'model' => 'res.partner',
            'group_id' => null,
            'perm_read' => true,
            'domain' => json_encode([['name', '=', 'Public']]),
        ]);
    });

    $env = new Environment($super->connection, $super->registry, 2);

    expect($env->model('res.partner')->search([['active', '=', true]])->count())->toBe(1);
});

test('anonymous user only receives global rules', function (): void {
    $super = recordRulesSuperEnv();
    $super->withAclBypass(function () use ($super): void {
        $sales = $super->model('res.groups')->create(['name' => 'Sales']);
        $super->model('res.partner')->create(['name' => 'Global']);
        $super->model('res.partner')->create(['name' => 'SalesOnly']);
        $super->model('ir.model.access')->create([
            'name' => 'Anon/partner read',
            'model' => 'res.partner',
            'group_id' => null,
            'perm_read' => true,
        ]);
        $super->model('ir.rule')->create([
            'name' => 'Global names',
            'model' => 'res.partner',
            'group_id' => null,
            'perm_read' => true,
            'domain' => json_encode([['name', '=', 'Global']]),
        ]);
        $super->model('ir.rule')->create([
            'name' => 'Sales names',
            'model' => 'res.partner',
            'group_id' => $sales->ids()[0],
            'perm_read' => true,
            'domain' => json_encode([['name', '=', 'SalesOnly']]),
        ]);
    });

    $anon = new Environment($super->connection, $super->registry, null);

    expect($anon->model('res.partner')->search()->count())->toBe(1)
        ->and($anon->model('res.partner')->search()->read()[0]['name'])->toBe('Global');
});

test('group rule does not apply to user outside that group', function (): void {
    $super = recordRulesSuperEnv();
    $baseline = $super->withAclBypass(function () use ($super): array {
        $sales = $super->model('res.groups')->create(['name' => 'Sales']);
        $other = $super->model('res.groups')->create(['name' => 'Other']);
        $super->model('res.users')->create(['name' => 'Administrator', 'login' => 'admin']);
        $outsider = $super->model('res.users')->create([
            'name' => 'Outsider',
            'login' => 'outsider',
            'group_ids' => $other->ids(),
        ]);
        $super->model('res.partner')->create(['name' => 'Visible']);
        $super->model('res.partner')->create(['name' => 'Hidden']);
        $super->model('ir.model.access')->create([
            'name' => 'Other/partner read',
            'model' => 'res.partner',
            'group_id' => $other->ids()[0],
            'perm_read' => true,
        ]);
        $super->model('ir.rule')->create([
            'name' => 'Sales hidden filter',
            'model' => 'res.partner',
            'group_id' => $sales->ids()[0],
            'perm_read' => true,
            'domain' => json_encode([['name', '=', 'Visible']]),
        ]);

        return ['outsider_id' => $outsider->ids()[0]];
    });

    $env = new Environment($super->connection, $super->registry, $baseline['outsider_id']);

    expect($env->model('res.partner')->search()->count())->toBe(2);
});

test('rules respect perm_read flag when collecting for read', function (): void {
    $super = recordRulesSuperEnv();
    $super->withAclBypass(function () use ($super): void {
        $super->model('res.partner')->create(['name' => 'Open']);
        $super->model('ir.model.access')->create([
            'name' => 'All/partner read',
            'model' => 'res.partner',
            'group_id' => null,
            'perm_read' => true,
        ]);
        $super->model('ir.rule')->create([
            'name' => 'Write-only filter',
            'model' => 'res.partner',
            'group_id' => null,
            'perm_read' => false,
            'perm_write' => true,
            'domain' => json_encode([['name', '=', 'Blocked']]),
        ]);
    });

    $env = new Environment($super->connection, $super->registry, 2);

    expect($env->collectRecordRules('res.partner', 'read'))->toBe([])
        ->and($env->model('res.partner')->search()->count())->toBe(1);
});

test('collectRecordRules uses perm_write when requested', function (): void {
    $super = recordRulesSuperEnv();
    $super->withAclBypass(fn () => $super->model('ir.rule')->create([
        'name' => 'Write filter',
        'model' => 'res.partner',
        'group_id' => null,
        'perm_read' => false,
        'perm_write' => true,
        'domain' => json_encode([['name', '=', 'X']]),
    ]));

    $env = new Environment($super->connection, $super->registry, 2);

    expect($env->collectRecordRules('res.partner', 'read'))->toBe([])
        ->and($env->collectRecordRules('res.partner', 'write'))->toHaveCount(1);
});

test('company_id placeholder resolves from environment context', function (): void {
    $super = recordRulesSuperEnv();
    $super->withAclBypass(function () use ($super): void {
        $super->model('res.partner')->create(['name' => 'In company', 'owner_id' => 10]);
        $super->model('res.partner')->create(['name' => 'Elsewhere', 'owner_id' => 20]);
        $super->model('ir.model.access')->create([
            'name' => 'All/partner read',
            'model' => 'res.partner',
            'group_id' => null,
            'perm_read' => true,
        ]);
        $super->model('ir.rule')->create([
            'name' => 'Company scope',
            'model' => 'res.partner',
            'group_id' => null,
            'perm_read' => true,
            'domain' => json_encode([['owner_id', '=', ['placeholder' => 'company_id']]]),
        ]);
    });

    $env = new Environment($super->connection, $super->registry, 2, ['company_id' => 10]);

    expect($env->model('res.partner')->search()->count())->toBe(1)
        ->and($env->model('res.partner')->search()->read()[0]['name'])->toBe('In company');
});

test('user_id placeholder alias matches uid', function (): void {
    $super = recordRulesSuperEnv();
    $userId = $super->withAclBypass(function () use ($super): int {
        $super->model('res.users')->create(['name' => 'Administrator', 'login' => 'admin']);
        $user = $super->model('res.users')->create(['name' => 'Pat', 'login' => 'pat']);
        $super->model('res.partner')->create(['name' => 'Mine', 'owner_id' => $user->ids()[0]]);
        $super->model('res.partner')->create(['name' => 'Other', 'owner_id' => 99]);
        $super->model('ir.model.access')->create([
            'name' => 'All/partner read',
            'model' => 'res.partner',
            'group_id' => null,
            'perm_read' => true,
        ]);
        $super->model('ir.rule')->create([
            'name' => 'By user_id',
            'model' => 'res.partner',
            'group_id' => null,
            'perm_read' => true,
            'domain' => json_encode([['owner_id', '=', ['placeholder' => 'user_id']]]),
        ]);

        return $user->ids()[0];
    });

    $env = new Environment($super->connection, $super->registry, $userId);

    expect($env->model('res.partner')->search()->count())->toBe(1);
});

test('placeholder inside in operator list resolves', function (): void {
    $super = recordRulesSuperEnv();
    $userId = $super->withAclBypass(function () use ($super): int {
        $super->model('res.users')->create(['name' => 'Administrator', 'login' => 'admin']);
        $user = $super->model('res.users')->create(['name' => 'Pat', 'login' => 'pat']);
        $super->model('res.partner')->create(['name' => 'Mine', 'owner_id' => $user->ids()[0]]);
        $super->model('res.partner')->create(['name' => 'Other', 'owner_id' => 99]);
        $super->model('ir.model.access')->create([
            'name' => 'All/partner read',
            'model' => 'res.partner',
            'group_id' => null,
            'perm_read' => true,
        ]);
        $super->model('ir.rule')->create([
            'name' => 'Owner in uid',
            'model' => 'res.partner',
            'group_id' => null,
            'perm_read' => true,
            'domain' => json_encode([['owner_id', 'in', [['placeholder' => 'uid']]]]),
        ]);

        return $user->ids()[0];
    });

    $env = new Environment($super->connection, $super->registry, $userId);

    expect($env->model('res.partner')->search()->count())->toBe(1)
        ->and($env->model('res.partner')->search()->read()[0]['name'])->toBe('Mine');
});

test('invalid rule domain json throws', function (): void {
    $super = recordRulesSuperEnv();
    $super->withAclBypass(fn () => $super->model('ir.rule')->create([
        'name' => 'Bad json',
        'model' => 'res.partner',
        'group_id' => null,
        'perm_read' => true,
        'domain' => 'not-json',
    ]));

    $env = new Environment($super->connection, $super->registry, 2);

    expect(fn () => $env->collectRecordRules('res.partner', 'read'))
        ->toThrow(\InvalidArgumentException::class, 'JSON array');
});

test('unknown placeholder throws', function (): void {
    expect(fn () => recordRulesSuperEnv()->resolveRulePlaceholder('bogus'))
        ->toThrow(\InvalidArgumentException::class, 'bogus');
});

test('browse and read by id does not apply record rules', function (): void {
    $super = recordRulesSuperEnv();
    $hiddenId = $super->withAclBypass(function () use ($super): int {
        $super->model('res.partner')->create(['name' => 'Public']);
        $hidden = $super->model('res.partner')->create(['name' => 'Secret']);
        $super->model('ir.model.access')->create([
            'name' => 'All/partner read',
            'model' => 'res.partner',
            'group_id' => null,
            'perm_read' => true,
        ]);
        $super->model('ir.rule')->create([
            'name' => 'Public only search',
            'model' => 'res.partner',
            'group_id' => null,
            'perm_read' => true,
            'domain' => json_encode([['name', '=', 'Public']]),
        ]);

        return $hidden->ids()[0];
    });

    $env = new Environment($super->connection, $super->registry, 2);

    expect($env->model('res.partner')->search()->count())->toBe(1)
        ->and($env->browse('res.partner', [$hiddenId])->read()[0]['name'])->toBe('Secret');
});

test('search by id respects record rules', function (): void {
    $super = recordRulesSuperEnv();
    $hiddenId = $super->withAclBypass(function () use ($super): int {
        $super->model('res.partner')->create(['name' => 'Public']);
        $hidden = $super->model('res.partner')->create(['name' => 'Secret']);
        $super->model('ir.model.access')->create([
            'name' => 'All/partner read',
            'model' => 'res.partner',
            'group_id' => null,
            'perm_read' => true,
        ]);
        $super->model('ir.rule')->create([
            'name' => 'Public only',
            'model' => 'res.partner',
            'group_id' => null,
            'perm_read' => true,
            'domain' => json_encode([['name', '=', 'Public']]),
        ]);

        return $hidden->ids()[0];
    });

    $env = new Environment($super->connection, $super->registry, 2);

    expect($env->model('res.partner')->search([['id', '=', $hiddenId]])->count())->toBe(0);
});

test('acl bypass on search skips record rules', function (): void {
    $super = recordRulesSuperEnv();
    $super->withAclBypass(function () use ($super): void {
        $super->model('res.partner')->create(['name' => 'Public']);
        $super->model('res.partner')->create(['name' => 'Secret']);
        $super->model('ir.model.access')->create([
            'name' => 'All/partner read',
            'model' => 'res.partner',
            'group_id' => null,
            'perm_read' => true,
        ]);
        $super->model('ir.rule')->create([
            'name' => 'Public only',
            'model' => 'res.partner',
            'group_id' => null,
            'perm_read' => true,
            'domain' => json_encode([['name', '=', 'Public']]),
        ]);
    });

    $env = new Environment($super->connection, $super->registry, 2);

    expect($env->model('res.partner')->search()->count())->toBe(1);

    $env->withAclBypass(function () use ($env): void {
        expect($env->model('res.partner')->search()->count())->toBe(2);
    });
});

test('rules for other models do not affect search', function (): void {
    $super = recordRulesSuperEnv();
    $super->withAclBypass(function () use ($super): void {
        $super->model('res.partner')->create(['name' => 'Partner']);
        $super->model('ir.model.access')->create([
            'name' => 'All/partner read',
            'model' => 'res.partner',
            'group_id' => null,
            'perm_read' => true,
        ]);
        $super->model('ir.rule')->create([
            'name' => 'Wrong model',
            'model' => 'res.users',
            'group_id' => null,
            'perm_read' => true,
            'domain' => json_encode([['login', '=', 'nobody']]),
        ]);
    });

    $env = new Environment($super->connection, $super->registry, 2);

    expect($env->model('res.partner')->search()->count())->toBe(1);
});

test('write still requires model access when rules exist', function (): void {
    $super = recordRulesSuperEnv();
    $partnerId = $super->withAclBypass(function () use ($super): int {
        $super->model('res.users')->create(['name' => 'Administrator', 'login' => 'admin']);
        $partner = $super->model('res.partner')->create(['name' => 'Open']);
        $super->model('ir.rule')->create([
            'name' => 'Any',
            'model' => 'res.partner',
            'group_id' => null,
            'perm_read' => true,
            'domain' => json_encode([['name', '=', 'Open']]),
        ]);

        return $partner->ids()[0];
    });

    $env = new Environment($super->connection, $super->registry, 2);

    expect(fn () => $env->browse('res.partner', [$partnerId])->write(['name' => 'Nope']))
        ->toThrow(AccessDeniedException::class, 'write');
});
