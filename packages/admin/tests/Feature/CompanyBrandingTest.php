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

test('branding falls back to application config name when company fields are empty', function (): void {
    config(['app.name' => 'Config App Name']);

    $base = app(Environment::class);
    $branding = CompanyBranding::forEnvironment(new Environment($base->connection, new \Velm\Registry, 1));

    expect($branding['app_name'])->toBe('Config App Name');
});

test('branding reads boolean and integer overrides from velm config', function (): void {
    $env = app(Environment::class);
    $row = $env->withAclBypass(
        fn () => $env->model('res.company')->search(limit: 1)->read(['id'])[0] ?? null,
    );

    $env->withAclBypass(
        fn () => $env->browse('res.company', [(int) $row['id']])->write([
            'show_header_brand_text' => null,
            'header_logo_height' => 0,
        ]),
    );

    config([
        'velm.branding.VELM_SHOW_HEADER_BRAND_TEXT' => 'false',
        'velm.branding.VELM_HEADER_LOGO_HEIGHT' => '72',
    ]);

    $branding = CompanyBranding::forEnvironment($env);

    expect($branding['show_header_brand_text'])->toBeFalse()
        ->and($branding['header_logo_height'])->toBe(72);
});

test('branding exposes per-company font stylesheet and css overrides', function (): void {
    $env = app(Environment::class);
    $row = $env->withAclBypass(
        fn () => $env->model('res.company')->search(limit: 1)->read(['id'])[0] ?? null,
    );

    $env->withAclBypass(
        fn () => $env->browse('res.company', [(int) $row['id']])->write([
            'font_family' => 'DM Sans',
        ]),
    );

    $branding = CompanyBranding::forEnvironment($env);

    expect($branding['company_font_family'])->toBe('DM Sans')
        ->and($branding['company_font_stylesheet_url'])->toContain('DM+Sans')
        ->and($branding['company_font_style'])->toContain('--font-sans');
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
