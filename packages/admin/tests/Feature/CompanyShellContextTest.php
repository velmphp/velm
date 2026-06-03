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

test('shell shares active company name for switcher', function (): void {
    $env = app(Environment::class);
    $row = $env->withAclBypass(
        fn () => $env->model('res.company')->search(limit: 1)->read(['id', 'name'])[0] ?? null,
    );

    expect($row)->not->toBeNull();

    $companyId = (int) $row['id'];
    $companyName = (string) $row['name'];

    $this->actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']));
    $this->withCookie(CompanyCookie::NAME, (string) $companyId);

    $this->get('/velm/apps');

    $shell = view()->shared('velmShell');

    expect($shell['current_company_id'])->toBe($companyId)
        ->and($shell['current_company_name'])->toBe($companyName);
});
