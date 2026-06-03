<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Velm\Company\CompanyCookie;
use Velm\Environment;
use Velm\Admin\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('switch company route sets cookie and redirects back', function (): void {
    $env = app(Environment::class);
    $companyId = $env->withAclBypass(
        fn () => $env->model('res.company')->search(limit: 1)->ids()[0] ?? null,
    );

    expect($companyId)->not->toBeNull();

    $this->actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']));

    $response = $this->post('/velm/switch-company', [
        'company_id' => (string) $companyId,
    ], ['HTTP_REFERER' => '/velm/apps']);

    $response->assertRedirect('/velm/apps');
    expect($response->headers->getCookies())->not->toBeEmpty();
});

test('panel middleware binds active company from cookie', function (): void {
    $env = app(Environment::class);
    $companyId = $env->withAclBypass(
        fn () => $env->model('res.company')->search(limit: 1)->ids()[0] ?? null,
    );

    expect($companyId)->not->toBeNull();

    $this->actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']));
    $this->withCookie(CompanyCookie::NAME, (string) $companyId);

    $this->get('/velm/apps');

    expect(app(Environment::class)->companyId())->toBe($companyId);
});

test('environment exposes company id from context', function (): void {
    $base = app(Environment::class);
    $scoped = $base->withContext(['company_id' => 42]);

    expect($scoped->companyId())->toBe(42)
        ->and($base->companyId())->toBeNull();
});

test('company cookie name is stable', function (): void {
    expect(CompanyCookie::NAME)->toBe('velm_company');
});
