<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Velm\Admin\Support\AppsCatalogMenuContext;
use Velm\Admin\Tests\TestCase;
use Velm\Views\Menu\MenuLayout;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('apps catalog routes use dedicated sidebar menu layout', function (): void {
    $request = Request::create('/velm/apps', 'GET');

    expect(AppsCatalogMenuContext::matches($request))->toBeTrue();

    $context = AppsCatalogMenuContext::build($request, app(\Velm\Environment::class));

    expect($context['menu_layout'])->toBe(MenuLayout::APPS_CATALOG)
        ->and($context['apps_states'])->toHaveCount(5)
        ->and($context['apps_summary'])->toHaveKeys(['needs_sync'])
        ->and($context['apps_catalog_url'])->toContain('/velm/apps');
});

test('apps detail route sets active module for sidebar', function (): void {
    $request = Request::create('/velm/apps/base', 'GET');
    $request->setRouteResolver(function () use ($request) {
        $route = new \Illuminate\Routing\Route('GET', '/velm/apps/{name}', []);
        $route->bind($request);
        $route->setParameter('name', 'base');

        return $route;
    });

    $context = AppsCatalogMenuContext::build($request, app(\Velm\Environment::class));

    expect($context['apps_active_module'])->toBe('base')
        ->and($context['menu_layout'])->toBe(MenuLayout::APPS_CATALOG);
});

test('module list routes keep standard apps layout', function (): void {
    $request = Request::create('/velm/views/partners/partner.list', 'GET');

    expect(AppsCatalogMenuContext::matches($request))->toBeFalse();
});
