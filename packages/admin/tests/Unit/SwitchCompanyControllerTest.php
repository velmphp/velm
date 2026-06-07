<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use Velm\Admin\Http\Controllers\SwitchCompanyController;
use Velm\Admin\Support\VelmPanel;
use Velm\Admin\Tests\TestCase;
use Velm\Company\CompanyCookie;
use Velm\Environment;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('switch company controller clears cookie for superuser all-companies mode', function (): void {
    $this->actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']));

    $request = Request::create('/velm/switch-company', 'POST');
    $request->headers->set('referer', '/velm/apps');

    $response = app(SwitchCompanyController::class)($request);

    expect($response->getTargetUrl())->toBe(url('/velm/apps'))
        ->and($response->headers->get('Set-Cookie'))->toContain(CompanyCookie::NAME.'=deleted');
});

test('switch company controller falls back to default company for non-superuser', function (): void {
    $env = app(Environment::class);
    $company = $env->model('res.company')->create(['name' => 'Default Switch Co']);
    $userId = $env->model('res.users')->create([
        'name' => 'Worker',
        'email' => 'worker-switch@test',
        'password' => 'x',
        'company_id' => $company->ids()[0],
    ])->ids()[0];

    $this->actingAs(new GenericUser(['id' => $userId, 'email' => 'worker-switch@test']));

    $request = Request::create('/velm/switch-company', 'POST');
    $response = app(SwitchCompanyController::class)($request);

    expect($response->headers->get('Set-Cookie'))->toContain(
        CompanyCookie::NAME.'='.$company->ids()[0],
    );
});

test('switch company controller clears cookie when user has no default company', function (): void {
    $env = app(Environment::class);
    $userId = $env->model('res.users')->create([
        'name' => 'No Company User',
        'email' => 'noco@test',
        'password' => 'x',
        'company_id' => null,
    ])->ids()[0];

    $this->actingAs(new GenericUser(['id' => $userId, 'email' => 'noco@test']));

    $response = app(SwitchCompanyController::class)(Request::create('/velm/switch-company', 'POST'));

    expect($response->headers->get('Set-Cookie'))->toContain(CompanyCookie::NAME.'=deleted');
});

test('switch company controller rejects invalid forbidden and missing companies', function (): void {
    $env = app(Environment::class);
    $allowed = $env->model('res.company')->create(['name' => 'Allowed Co']);
    $forbidden = $env->model('res.company')->create(['name' => 'Forbidden Co']);
    $userId = $env->model('res.users')->create([
        'name' => 'Scoped Worker',
        'email' => 'scoped-switch@test',
        'password' => 'x',
        'company_id' => $allowed->ids()[0],
    ])->ids()[0];

    $this->actingAs(new GenericUser(['id' => $userId, 'email' => 'scoped-switch@test']));

    $invalid = app(SwitchCompanyController::class)(Request::create('/velm/switch-company', 'POST', [
        'company_id' => 'abc',
    ]));
    expect($invalid->headers->get('Set-Cookie'))->toContain(CompanyCookie::NAME.'=deleted');

    $denied = app(SwitchCompanyController::class)(Request::create('/velm/switch-company', 'POST', [
        'company_id' => (string) $forbidden->ids()[0],
    ]));
    expect($denied->getSession()->get('error'))->not->toBeNull();

    $missing = app(SwitchCompanyController::class)(Request::create('/velm/switch-company', 'POST', [
        'company_id' => '999999',
    ]));
    expect($missing->getSession()->get('error'))->not->toBeNull();
});

test('switch company controller redirects to panel home without referer', function (): void {
    $env = app(Environment::class);
    $companyId = $env->model('res.company')->search(limit: 1)->ids()[0];

    $this->actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']));

    $response = app(SwitchCompanyController::class)(Request::create('/velm/switch-company', 'POST', [
        'company_id' => (string) $companyId,
    ]));

    expect($response->getTargetUrl())->toBe(VelmPanel::getUrl());
});
