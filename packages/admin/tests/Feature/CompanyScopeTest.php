<?php

declare(strict_types=1);

use Velm\Environment;
use Velm\Exception\AccessDeniedException;
use Velm\Admin\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('search on company-scoped models filters by active company', function (): void {
    $env = app(Environment::class);

    $companyA = $env->model('res.company')->create(['name' => 'A Corp']);
    $companyB = $env->model('res.company')->create(['name' => 'B Corp']);

    $env->model('res.partner')->create(['name' => 'Partner A', 'company_id' => $companyA->ids()[0]]);
    $env->model('res.partner')->create(['name' => 'Partner B', 'company_id' => $companyB->ids()[0]]);

    $scoped = $env->withContext(['company_id' => $companyA->ids()[0]]);

    $names = array_column($scoped->model('res.partner')->search()->read(['name']), 'name');

    expect($names)->toBe(['Partner A']);
});

test('create stamps company_id when active company is set', function (): void {
    $env = app(Environment::class);
    $company = $env->model('res.company')->create(['name' => 'Acme']);
    $scoped = $env->withContext(['company_id' => $company->ids()[0]]);

    $partner = $scoped->model('res.partner')->create(['name' => 'New partner']);

    expect($partner->read()[0]['company_id'])->toBe($company->ids()[0]);
});

test('read rejects records outside active company', function (): void {
    $env = app(Environment::class);
    $companyA = $env->model('res.company')->create(['name' => 'A']);
    $companyB = $env->model('res.company')->create(['name' => 'B']);
    $partnerB = $env->model('res.partner')->create([
        'name' => 'Outside',
        'company_id' => $companyB->ids()[0],
    ]);

    $scoped = $env->withContext(['company_id' => $companyA->ids()[0]]);

    expect(fn () => $scoped->browse('res.partner', $partnerB->ids())->read())
        ->toThrow(AccessDeniedException::class);
});

test('resolve active company falls back to user default', function (): void {
    $env = app(Environment::class);
    $company = $env->model('res.company')->create(['name' => 'Default Co']);
    $user = $env->model('res.users')->create([
        'name' => 'Worker',
        'email' => 'worker@velm.test',
        'password' => 'x',
        'company_id' => $company->ids()[0],
    ]);

    $userEnv = new Environment(
        $env->connection,
        $env->registry,
        $user->ids()[0],
    );

    expect($userEnv->resolveActiveCompanyId(null))->toBe($company->ids()[0]);
});

test('non-superuser cannot use all-companies mode', function (): void {
    $env = app(Environment::class);
    $company = $env->model('res.company')->create(['name' => 'Only']);
    $user = $env->model('res.users')->create([
        'name' => 'Worker',
        'email' => 'worker2@velm.test',
        'password' => 'x',
        'company_id' => $company->ids()[0],
    ]);

    $userEnv = new Environment(
        $env->connection,
        $env->registry,
        $user->ids()[0],
    );

    expect($userEnv->allowsAllCompaniesMode())->toBeFalse()
        ->and($userEnv->resolveActiveCompanyId(null))->toBe($company->ids()[0]);
});
