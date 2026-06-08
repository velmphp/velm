<?php

declare(strict_types=1);

use Velm\Cron\CronJob;
use Velm\Database\PdoConnection;
use Velm\Environment;
use Velm\Exception\AccessDeniedException;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\SystemAudit\AuditLogger;
use Velm\Modules\SystemAudit\AuditLoginLogger;
use Velm\Modules\SystemAudit\AuditRecordListener;
use Velm\Modules\SystemAudit\AuditRequestContext;
use Velm\Modules\SystemAudit\AuditRetention;
use Velm\Modules\SystemAudit\AuditUserLifecycle;
use Velm\Modules\SystemAudit\SystemAuditCron;
use Velm\Modules\SystemAudit\SystemAuditInstallHooks;
use Velm\Modules\Tests\TestCase;
use Velm\Recordset\Recordset;
use Velm\Registry;
use Velm\Schema\SchemaBuilder;
use Velm\Support\RecordChangeNotifier;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }

    AuditRequestContext::simulateStandaloneRuntime(false);
    AuditRecordListener::register();
});

function installSystemAudit(): Environment
{
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('system_audit', $roots);

    return $installer->environment($roots);
}

function installBaseOnly(): Environment
{
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);

    return $installer->environment($roots);
}

test('system audit module installs audit models and retention cron', function (): void {
    $env = installSystemAudit();

    expect($env->registry->has('ir.audit.log'))->toBeTrue()
        ->and($env->registry->has('ir.login.log'))->toBeTrue()
        ->and($env->registry->has('ir.user.lifecycle'))->toBeTrue();

    $cron = $env->model('ir.cron')->search([['name', '=', 'Audit log retention']], limit: 1);

    expect($cron->count())->toBe(1);
});

test('record changes are logged to ir.audit.log', function (): void {
    $env = installSystemAudit();

    $company = $env->model('res.company')->create(['name' => 'Audit Co']);
    $companyId = $company->ids()[0];

    $logs = $env->model('ir.audit.log')->search([['model', '=', 'res.company']])->read(['action', 'res_id', 'company_id']);

    expect($logs)->not->toBeEmpty()
        ->and($logs[0]['action'])->toBe('create')
        ->and($logs[0]['res_id'])->toBe($companyId)
        ->and($logs[0]['company_id'])->toBe($companyId);
});

test('company writes are scoped to the edited company not session context', function (): void {
    $env = installSystemAudit();

    $company = $env->model('res.company')->create(['name' => 'Scoped Co']);
    $companyId = $company->ids()[0];

    $other = $env->model('res.company')->create(['name' => 'Other Co']);
    $otherId = $other->ids()[0];

    $env = $env->withContext(['company_id' => $otherId]);

    $env->browse('res.company', [$companyId])->write(['name' => 'Scoped Co Updated']);

    $searchEnv = $env->withContext(['company_id' => null]);
    $log = $searchEnv->withAclBypass(fn () => $searchEnv->model('ir.audit.log')->search([
        ['model', '=', 'res.company'],
        ['res_id', '=', $companyId],
        ['action', '=', 'write'],
    ], limit: 1)->read(['company_id'])[0] ?? null);

    expect($log)->not->toBeNull()
        ->and($log['company_id'])->toBe($companyId)
        ->and($log['company_id'])->not->toBe($otherId);

    $visible = $env->withContext(['company_id' => $companyId])->model('ir.audit.log')->search([
        ['model', '=', 'res.company'],
        ['res_id', '=', $companyId],
        ['action', '=', 'write'],
    ], limit: 1)->count();

    expect($visible)->toBe(1);
});

test('unlink operations are logged with empty new values', function (): void {
    $env = installSystemAudit();

    $company = $env->model('res.company')->create(['name' => 'Delete Me']);
    $companyId = $company->ids()[0];

    $env->browse('res.company', [$companyId])->unlink();

    $log = $env->withAclBypass(fn () => $env->model('ir.audit.log')->search([
        ['model', '=', 'res.company'],
        ['res_id', '=', $companyId],
        ['action', '=', 'unlink'],
    ], limit: 1)->read(['new_values', 'old_values'])[0] ?? null);

    expect($log)->not->toBeNull()
        ->and($log['new_values'])->toBeNull()
        ->and($log['old_values'])->not->toBeNull();
});

test('audit models are not self-logged', function (): void {
    $env = installSystemAudit();

    $before = $env->model('ir.audit.log')->search()->count();

    $env->withAclBypass(fn () => $env->model('ir.audit.log')->create([
        'name' => 'Manual entry',
        'action' => 'write',
        'model' => 'res.company',
        'res_id' => 1,
    ]));

    $after = $env->model('ir.audit.log')->search()->count();

    expect($after)->toBe($before + 1)
        ->and($env->model('ir.audit.log')->search([
            ['model', '=', 'ir.audit.log'],
        ])->count())->toBe(0);
});

test('user lifecycle events are tracked on create and activation changes', function (): void {
    $env = installSystemAudit();

    $user = $env->model('res.users')->create([
        'name' => 'Lifecycle User',
        'email' => 'lifecycle@velm.test',
        'active' => true,
    ]);
    $userId = $user->ids()[0];

    $events = $env->model('ir.user.lifecycle')->search([['user_id', '=', $userId]])->read(['event']);

    expect(collect($events)->pluck('event')->all())->toContain('created');

    $user->write(['active' => false]);

    $events = $env->model('ir.user.lifecycle')->search([['user_id', '=', $userId]])->read(['event']);

    expect(collect($events)->pluck('event')->all())->toContain('deactivated');
});

test('user lifecycle tracks password changes groups reactivation and deletion', function (): void {
    $env = installSystemAudit();

    $group = $env->model('res.groups')->create(['name' => 'Audit Testers']);
    $groupId = $group->ids()[0];

    $user = $env->model('res.users')->create([
        'name' => 'Full Lifecycle',
        'email' => 'full-lifecycle@velm.test',
        'active' => true,
        'group_ids' => [],
    ]);
    $userId = $user->ids()[0];

    $user->write(['password' => 'new-secret']);
    $user->write(['group_ids' => [$groupId]]);
    $user->write(['active' => false]);
    $user->write(['active' => true]);
    $user->unlink();

    $events = collect($env->model('ir.user.lifecycle')->search([['user_id', '=', $userId]])->read(['event', 'detail']))
        ->pluck('event')
        ->all();

    expect($events)->toContain('password_changed')
        ->and($events)->toContain('groups_changed')
        ->and($events)->toContain('deactivated')
        ->and($events)->toContain('activated')
        ->and($events)->toContain('deleted');
});

test('login logger writes ir.login.log rows', function (): void {
    $env = installSystemAudit();

    AuditLoginLogger::log($env, 'login_success', 1, 'admin@velm.test');

    $rows = $env->model('ir.login.log')->search()->read(['event', 'email']);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['event'])->toBe('login_success')
        ->and($rows[0]['email'])->toBe('admin@velm.test');
});

test('audit retention purges rows older than configured cutoff', function (): void {
    $env = installSystemAudit();
    config(['velm.audit.retention_days' => 30]);

    $env->withAclBypass(function () use ($env): void {
        $env->model('ir.audit.log')->create([
            'name' => 'Old entry',
            'action' => 'write',
            'model' => 'res.company',
            'res_id' => 1,
            'created_at' => '2020-01-01 00:00:00',
        ]);
        $env->model('ir.login.log')->create([
            'event' => 'login_success',
            'email' => 'old@velm.test',
            'created_at' => '2020-01-01 00:00:00',
        ]);
        $env->model('ir.user.lifecycle')->create([
            'user_id' => 1,
            'event' => 'created',
            'created_at' => '2020-01-01 00:00:00',
        ]);
    });

    expect($env->model('ir.audit.log')->search([['name', '=', 'Old entry']])->count())->toBe(1)
        ->and($env->model('ir.login.log')->search([['email', '=', 'old@velm.test']])->count())->toBe(1);

    $purged = AuditRetention::purge($env);

    expect($purged)->toBeGreaterThanOrEqual(3)
        ->and($env->model('ir.audit.log')->search([['name', '=', 'Old entry']])->count())->toBe(0)
        ->and($env->model('ir.login.log')->search([['email', '=', 'old@velm.test']])->count())->toBe(0);
});

test('audit purge cron action runs via ir.cron', function (): void {
    $env = installSystemAudit();
    config(['velm.audit.retention_days' => 1]);

    $env->withAclBypass(function () use ($env): void {
        $env->model('ir.audit.log')->create([
            'name' => 'Stale',
            'action' => 'write',
            'created_at' => '2020-01-01 00:00:00',
        ]);
    });

    $cron = $env->model('ir.cron')->search([['name', '=', 'Audit log retention']], limit: 1);
    $cron->write(['nextcall' => '2000-01-01 00:00:00']);

    $executed = CronJob::runDue($env);

    expect($executed)->toContain('Audit log retention')
        ->and($env->model('ir.audit.log')->search([['name', '=', 'Stale']])->count())->toBe(0);
});

test('audit logs are append-only without acl bypass', function (): void {
    $env = installSystemAudit();

    expect(fn () => $env->model('ir.audit.log')->create([
        'name' => 'Blocked',
        'action' => 'write',
    ]))->toThrow(AccessDeniedException::class);

    $id = $env->withAclBypass(fn () => $env->model('ir.audit.log')->create([
        'name' => 'Existing',
        'action' => 'write',
    ])->ids()[0]);

    expect(fn () => $env->browse('ir.audit.log', [$id])->write(['name' => 'Changed']))
        ->toThrow(AccessDeniedException::class)
        ->and(fn () => $env->browse('ir.audit.log', [$id])->unlink())
        ->toThrow(AccessDeniedException::class);
});

test('audit logger resolves company_id from record values', function (): void {
    $env = installSystemAudit();

    $company = $env->model('res.company')->create(['name' => 'User Co']);
    $companyId = $company->ids()[0];

    $user = $env->model('res.users')->create([
        'name' => 'Company User',
        'email' => 'company-user@velm.test',
        'company_id' => $companyId,
    ]);
    $userId = $user->ids()[0];

    $log = $env->withAclBypass(fn () => $env->model('ir.audit.log')->search([
        ['model', '=', 'res.users'],
        ['res_id', '=', $userId],
        ['action', '=', 'create'],
    ], limit: 1)->read(['company_id'])[0] ?? null);

    expect($log)->not->toBeNull()
        ->and($log['company_id'])->toBe($companyId);
});

test('audit logger no-ops when audit module is unavailable', function (): void {
    $env = installBaseOnly();

    AuditLogger::log($env, 'res.company', 1, 'write', ['name' => 'Before'], ['name' => 'After'], 'Custom summary');

    expect($env->registry->has('ir.audit.log'))->toBeFalse();
});

test('audit login logger no-ops when login log model is unavailable', function (): void {
    $env = installBaseOnly();

    AuditLoginLogger::log($env, 'login_failed', null, 'guest@test');

    expect($env->registry->has('ir.login.log'))->toBeFalse();
});

test('audit user lifecycle helpers no-op when model is unavailable', function (): void {
    $env = installBaseOnly();

    AuditUserLifecycle::log($env, 1, 'created');
    AuditUserLifecycle::trackCreate($env, 1, ['name' => 'N']);
    AuditUserLifecycle::trackWrite($env, 1, ['active' => false]);
    AuditUserLifecycle::trackDelete($env, 1);

    expect($env->registry->has('ir.user.lifecycle'))->toBeFalse();
});

test('audit user lifecycle trackWrite ignores empty value sets', function (): void {
    $env = installSystemAudit();

    $user = $env->model('res.users')->create([
        'name' => 'No-op Write',
        'email' => 'noop-write@velm.test',
    ]);
    $userId = $user->ids()[0];
    $before = $env->model('ir.user.lifecycle')->search([['user_id', '=', $userId]])->count();

    AuditUserLifecycle::trackWrite($env, $userId, []);

    expect($env->model('ir.user.lifecycle')->search([['user_id', '=', $userId]])->count())->toBe($before);
});

test('audit retention skips models that are not installed', function (): void {
    $env = installBaseOnly();

    expect(AuditRetention::purge($env))->toBe(0);
});

test('system audit cron seeding is idempotent', function (): void {
    $env = installSystemAudit();

    SystemAuditCron::seedRetention($env);

    expect($env->model('ir.cron')->search([['name', '=', 'Audit log retention']])->count())->toBe(1);
});

test('system audit cron no-ops without ir.cron model', function (): void {
    $env = Registry::using(function (Registry $registry): Environment {
        $connection = PdoConnection::sqliteMemory();
        (new SchemaBuilder($connection))->syncRegistry($registry);

        return new Environment($connection, $registry, uid: 1);
    });

    SystemAuditCron::seedRetention($env);

    expect($env->registry->has('ir.cron'))->toBeFalse();
});

test('system audit install hooks update existing access grants on reinstall', function (): void {
    $env = installSystemAudit();

    $before = $env->model('ir.model.access')->search([
        ['model', '=', 'ir.audit.log'],
    ])->read(['perm_read', 'perm_write'])[0];

    SystemAuditInstallHooks::install($env);

    $after = $env->model('ir.model.access')->search([
        ['model', '=', 'ir.audit.log'],
    ])->read(['perm_read', 'perm_write'])[0];

    expect($before['perm_read'])->toBeTrue()
        ->and($before['perm_write'])->toBeFalse()
        ->and($after)->toBe($before);
});

test('system audit install hooks no-op without access model or admin group', function (): void {
    $bareEnv = Registry::using(function (Registry $registry): Environment {
        $connection = PdoConnection::sqliteMemory();
        (new SchemaBuilder($connection))->syncRegistry($registry);

        return new Environment($connection, $registry, uid: 1);
    });

    SystemAuditInstallHooks::install($bareEnv);

    $env = installSystemAudit();
    $admin = $env->model('res.groups')->search([['name', '=', 'Admin']], limit: 1);
    $admin->unlink();

    SystemAuditInstallHooks::install($env);

    expect($env->model('res.groups')->search([['name', '=', 'Admin']])->count())->toBe(0);
});

test('audit request context captures request metadata and standalone fallbacks', function (): void {
    AuditRequestContext::simulateStandaloneRuntime(true);

    expect(AuditRequestContext::capture())->toBe([
        'ip' => '',
        'user_agent' => '',
        'session_id' => '',
    ])->and(AuditRequestContext::sessionLifetimeMinutes())->toBe(120);

    AuditRequestContext::simulateStandaloneRuntime(false);
    config(['session.lifetime' => 45]);

    expect(AuditRequestContext::sessionLifetimeMinutes())->toBe(45);
});

test('record change notifier no-ops when listener is not registered', function (): void {
    $env = installSystemAudit();
    RecordChangeNotifier::reset();

    $company = $env->model('res.company')->create(['name' => 'Silent Co']);

    expect($env->model('ir.audit.log')->search([
        ['model', '=', 'res.company'],
        ['res_id', '=', $company->ids()[0]],
    ])->count())->toBe(0);
});

test('audit record listener ignores changes when audit log model is unavailable', function (): void {
    $env = installBaseOnly();
    AuditRecordListener::register();

    $env->model('res.company')->create(['name' => 'No Audit Module']);

    expect($env->registry->has('ir.audit.log'))->toBeFalse();
});

test('audit record listener skips lifecycle tracking when lifecycle model is unavailable', function (): void {
    $env = installBaseOnly();
    $user = $env->model('res.users')->create([
        'name' => 'Lifecycle Skipped',
        'email' => 'lifecycle-skipped@velm.test',
    ]);

    $method = new ReflectionMethod(AuditRecordListener::class, 'trackUserLifecycle');
    $method->setAccessible(true);
    $method->invoke(null, $env, $user, ['name' => 'Lifecycle Skipped'], 'create', []);

    expect($env->registry->has('ir.user.lifecycle'))->toBeFalse();
});

test('audit record listener ignores unknown user lifecycle operations', function (): void {
    $env = installSystemAudit();
    $user = $env->model('res.users')->create([
        'name' => 'Unknown Op',
        'email' => 'unknown-op@velm.test',
    ]);
    $before = $env->model('ir.user.lifecycle')->search([['user_id', '=', $user->ids()[0]]])->count();

    $method = new ReflectionMethod(AuditRecordListener::class, 'trackUserLifecycle');
    $method->setAccessible(true);
    $method->invoke(null, $env, $user, [], 'unknown', []);

    expect($env->model('ir.user.lifecycle')->search([['user_id', '=', $user->ids()[0]]])->count())->toBe($before);
});

afterEach(function (): void {
    AuditRequestContext::simulateStandaloneRuntime(false);
    RecordChangeNotifier::reset();
});
