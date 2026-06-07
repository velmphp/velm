<?php

declare(strict_types=1);

use Velm\Environment;
use Velm\Exception\AccessDeniedException;
use Velm\Framework\Tests\TestCase;
use Velm\Framework\VelmManager;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }

    config([
        'velm.addon_paths' => [dirname(__DIR__, 2).'/modules/modules'],
        'velm.bootstrap_modules' => ['base'],
    ]);

    app(VelmManager::class)->installBootstrap();
    app(VelmManager::class)->install('partners');
});

test('environment company helpers resolve timezone and active company', function (): void {
    $env = app(Environment::class);
    $companyId = $env->model('res.company')->search(limit: 1)->ids()[0];

    $env = $env->withContext([
        'company_id' => $companyId,
        'timezone' => 'America/New_York',
    ]);

    expect($env->companyId())->toBe($companyId)
        ->and($env->timezone())->toBe('America/New_York')
        ->and($env->resolveCompanyTimezone($companyId))->not->toBe('')
        ->and($env->resolveActiveCompanyId($companyId))->toBe($companyId)
        ->and($env->modelHasCompanyField('res.partner'))->toBeTrue()
        ->and($env->companySearchConstraints('res.partner'))->toBe([['company_id', '=', $companyId]]);
});

test('environment enforce company on create and write', function (): void {
    $env = app(Environment::class);
    $companyId = $env->model('res.company')->search(limit: 1)->ids()[0];
    $env = $env->withContext(['company_id' => $companyId]);

    $partner = $env->model('res.partner')->create(['name' => 'Scoped']);
    $row = $partner->read(['company_id'])[0];

    expect($row['company_id'])->toBe($companyId);

    expect(fn () => $env->model('res.partner')->create([
        'name' => 'Wrong',
        'company_id' => $companyId + 999,
    ]))->toThrow(AccessDeniedException::class);

    expect(fn () => $partner->write(['company_id' => $companyId + 999]))
        ->toThrow(AccessDeniedException::class);
});

test('environment user default company and allowed companies for non superuser', function (): void {
    $env = app(Environment::class);
    $companyId = $env->model('res.company')->search(limit: 1)->ids()[0];
    $userId = $env->model('res.users')->create([
        'name' => 'Regular',
        'email' => 'regular@velm.test',
        'password' => 'secret',
        'company_id' => $companyId,
    ])->ids()[0];

    $userEnv = new Environment(
        $env->connection,
        $env->registry,
        $userId,
        ['company_id' => $companyId],
        $env->cache,
    );

    expect($userEnv->userDefaultCompanyId())->toBe($companyId)
        ->and($userEnv->allowedCompanyIds())->toBe([$companyId])
        ->and($userEnv->allowsAllCompaniesMode())->toBeFalse()
        ->and($userEnv->resolveActiveCompanyId(null))->toBe($companyId);
});

test('environment access flags and null uid group cache', function (): void {
    $env = app(\Velm\Environment::class);

    expect($env->accessFlags('res.partner'))->toHaveKeys(['read', 'write', 'create', 'unlink'])
        ->and($env->hasAccess('res.partner', 'read'))->toBeTrue();

    $anonEnv = new \Velm\Environment(
        $env->connection,
        $env->registry,
        null,
        [],
        $env->cache,
    );

    expect($anonEnv->userGroupIds())->toBe([])
        ->and($anonEnv->userDefaultCompanyId())->toBeNull()
        ->and($anonEnv->companyExists(1))->toBeTrue();
});

test('environment resolve company timezone falls back without company model context', function (): void {
    $env = app(\Velm\Environment::class)->withContext(['company_id' => null]);

    expect($env->resolveCompanyTimezone(null))->toBe('UTC')
        ->and($env->companySearchConstraints('res.partner'))->toBe([]);
});

test('environment superuser allowed companies includes all companies', function (): void {
    $env = app(Environment::class);

    expect($env->allowedCompanyIds())->not->toBeEmpty()
        ->and($env->allowsAllCompaniesMode())->toBeTrue()
        ->and($env->resolveActiveCompanyId(null))->toBeNull()
        ->and($env->modelHasCompanyField('res.partner'))->toBeTrue();
});
