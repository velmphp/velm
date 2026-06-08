<?php

declare(strict_types=1);

use Velm\Cron\CronJob;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\SystemAudit\AuditLoginLogger;
use Velm\Modules\SystemAudit\AuditRetention;
use Velm\Modules\Tests\TestCase;
use Velm\Support\RecordChangeNotifier;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

function installSystemAudit(): \Velm\Environment
{
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('system_audit', $roots);

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
    });

    expect($env->model('ir.audit.log')->search([['name', '=', 'Old entry']])->count())->toBe(1)
        ->and($env->model('ir.login.log')->search([['email', '=', 'old@velm.test']])->count())->toBe(1);

    $purged = AuditRetention::purge($env);

    expect($purged)->toBeGreaterThanOrEqual(2)
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

afterEach(function (): void {
    RecordChangeNotifier::reset();
});
