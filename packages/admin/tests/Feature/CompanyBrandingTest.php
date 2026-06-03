<?php

declare(strict_types=1);

use Velm\Admin\Support\CompanyBranding;
use Velm\Environment;
use Velm\Admin\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('branding uses company name when application name is empty', function (): void {
    $env = app(Environment::class);

    $row = $env->withAclBypass(
        fn () => $env->model('res.company')->search(limit: 1)->read(['id', 'name', 'app_name'])[0] ?? null,
    );

    expect($row)->not->toBeNull();

    $env->withAclBypass(
        fn () => $env->browse('res.company', [(int) $row['id']])->write([
            'app_name' => null,
            'name' => 'Acme Corp',
        ]),
    );

    $branding = CompanyBranding::forEnvironment($env);

    expect($branding['app_name'])->toBe('Acme Corp');
});

test('branding prefers application name over company name', function (): void {
    $env = app(Environment::class);

    $row = $env->withAclBypass(
        fn () => $env->model('res.company')->search(limit: 1)->read(['id'])[0] ?? null,
    );

    expect($row)->not->toBeNull();

    $env->withAclBypass(
        fn () => $env->browse('res.company', [(int) $row['id']])->write([
            'app_name' => 'Panel Title',
            'name' => 'Acme Corp',
        ]),
    );

    $branding = CompanyBranding::forEnvironment($env);

    expect($branding['app_name'])->toBe('Panel Title');
});

test('login page title uses shared shell branding', function (): void {
    config(['app.name' => 'Laravel']);

    $env = app(Environment::class);
    $row = $env->withAclBypass(
        fn () => $env->model('res.company')->search(limit: 1)->read(['id'])[0] ?? null,
    );

    $env->withAclBypass(
        fn () => $env->browse('res.company', [(int) $row['id']])->write([
            'app_name' => 'Velm Demo',
            'name' => 'Ignored When App Name Set',
        ]),
    );

    $this->get('/velm/login');

    $shell = view()->shared('velmShell');

    expect($shell['app_name'])->toBe('Velm Demo')
        ->and($this->get('/velm/login')->getContent())->toContain('Velm Demo');
});
