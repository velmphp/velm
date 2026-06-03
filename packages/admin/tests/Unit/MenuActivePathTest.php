<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Velm\Admin\Pages\PartnerListPage;
use Velm\Admin\Pages\StoredViewListPage;
use Velm\Admin\Support\MenuActivePath;
use Velm\Admin\Tests\TestCase;

uses(TestCase::class);

test('menu active path resolves livewire page component from route action', function (): void {
    $request = Request::create('/velm/partner-list', 'GET');
    $route = new Route(['GET'], '/velm/partner-list', [
        'uses' => 'Livewire\Mechanisms\HandleRouting\LivewirePageController@__invoke',
        'livewire_component' => PartnerListPage::class,
    ]);
    $route->bind($request);
    $request->setRouteResolver(fn () => $route);

    expect(MenuActivePath::forRequest($request))
        ->toBe('/velm/views/partners/partner.list');
});

test('menu active path resolves stored view list routes', function (): void {
    $request = Request::create('/velm/views/partners/partner.list', 'GET');
    $route = new Route(['GET'], '/velm/views/{module}/{viewName}', [
        'uses' => 'Livewire\Mechanisms\HandleRouting\LivewirePageController@__invoke',
        'livewire_component' => StoredViewListPage::class,
    ]);
    $route->bind($request);
    $route->setParameter('module', 'partners');
    $route->setParameter('viewName', 'partner.list');
    $request->setRouteResolver(fn () => $route);

    expect(MenuActivePath::forRequest($request))
        ->toBe('/velm/views/partners/partner.list');
});
