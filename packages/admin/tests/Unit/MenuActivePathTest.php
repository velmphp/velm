<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Velm\Admin\Pages\DashboardPage;
use Velm\Admin\Pages\FileLibraryPage;
use Velm\Admin\Pages\FilePropertiesPage;
use Velm\Admin\Pages\PartnerListPage;
use Velm\Admin\Pages\StoredViewCreatePage;
use Velm\Admin\Pages\StoredViewEditPage;
use Velm\Admin\Pages\StoredViewListPage;
use Velm\Admin\Pages\StoredViewPage;
use Velm\Admin\Pages\StoredViewRecordPage;
use Velm\Admin\Pages\WorkflowBuilderPage;
use Velm\Admin\Pages\WorkflowInboxPage;
use Velm\Admin\Support\MenuActivePath;
use Velm\Admin\Tests\TestCase;
use Velm\Framework\VelmManager;

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

test('menu active path resolves dashboard file and workflow shell routes', function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }

    app(VelmManager::class)->install('workflow');
    app(VelmManager::class)->install('file_manager');

    $dashboard = Request::create('/velm/dashboard', 'GET');
    $dashboardRoute = new Route(['GET'], '/velm/dashboard', [
        'livewire_component' => DashboardPage::class,
    ]);
    $dashboardRoute->bind($dashboard);
    $dashboard->setRouteResolver(fn () => $dashboardRoute);

    $library = Request::create('/web/files/library', 'GET');
    $libraryRoute = new Route(['GET'], '/web/files/library', [
        'livewire_component' => FileLibraryPage::class,
    ]);
    $libraryRoute->bind($library);
    $library->setRouteResolver(fn () => $libraryRoute);

    $inbox = Request::create('/web/workflow/inbox', 'GET');
    $inboxRoute = new Route(['GET'], '/web/workflow/inbox', [
        'livewire_component' => WorkflowInboxPage::class,
    ]);
    $inboxRoute->bind($inbox);
    $inbox->setRouteResolver(fn () => $inboxRoute);

    $builder = Request::create('/web/workflow/9/build', 'GET');
    $builderRoute = new Route(['GET'], '/web/workflow/{workflowId}/build', [
        'livewire_component' => WorkflowBuilderPage::class,
    ]);
    $builderRoute->bind($builder);
    $builderRoute->setParameter('workflowId', '9');
    $builder->setRouteResolver(fn () => $builderRoute);

    expect(MenuActivePath::forRequest($dashboard))->toBe('/velm/dashboard')
        ->and(MenuActivePath::forRequest($library))->toBe('/web/files/library')
        ->and(MenuActivePath::forRequest($inbox))->toBe('/web/workflow/inbox')
        ->and(MenuActivePath::forRequest($builder))->toBe('/web/workflow/9/build');
});

test('menu active path resolves stored view analytics routes to list href', function (): void {
    $request = Request::create('/velm/views/partners/partner.kanban', 'GET');
    $route = new Route(['GET'], '/velm/views/{module}/{viewName}', [
        'livewire_component' => StoredViewPage::class,
    ]);
    $route->bind($request);
    $route->setParameter('module', 'partners');
    $route->setParameter('viewName', 'partner.kanban');
    $request->setRouteResolver(fn () => $route);

    expect(MenuActivePath::forRequest($request))
        ->toBe('/velm/views/partners/partner.list');
});

test('menu active path resolves stored view form routes to list href', function (): void {
    $request = Request::create('/velm/views/partners/partner.form/create', 'GET');
    $route = new Route(['GET'], '/velm/views/{module}/{viewName}/create', [
        'livewire_component' => StoredViewCreatePage::class,
    ]);
    $route->bind($request);
    $route->setParameter('module', 'partners');
    $route->setParameter('viewName', 'partner.form');
    $request->setRouteResolver(fn () => $route);

    expect(MenuActivePath::forRequest($request))->toBe('/velm/views/partners/partner.list');
});

test('menu active path resolves stored view record and edit routes to list href', function (): void {
    $record = Request::create('/velm/views/partners/partner.detail/4', 'GET');
    $recordRoute = new Route(['GET'], '/velm/views/{module}/{viewName}/{record}', [
        'livewire_component' => StoredViewRecordPage::class,
    ]);
    $recordRoute->bind($record);
    $recordRoute->setParameter('module', 'partners');
    $recordRoute->setParameter('viewName', 'partner.detail');
    $record->setRouteResolver(fn () => $recordRoute);

    $edit = Request::create('/velm/views/partners/partner.form/4/edit', 'GET');
    $editRoute = new Route(['GET'], '/velm/views/{module}/{viewName}/{record}/edit', [
        'livewire_component' => StoredViewEditPage::class,
    ]);
    $editRoute->bind($edit);
    $editRoute->setParameter('module', 'partners');
    $editRoute->setParameter('viewName', 'partner.form');
    $edit->setRouteResolver(fn () => $editRoute);

    expect(MenuActivePath::forRequest($record))->toBe('/velm/views/partners/partner.list')
        ->and(MenuActivePath::forRequest($edit))->toBe('/velm/views/partners/partner.list');
});

test('menu active path resolves shell json routes and controller uses fallback', function (): void {
    $tree = Request::create('/web/files/tree', 'GET');
    $treeRoute = new Route(['GET'], '/web/files/tree', [
        'uses' => 'Velm\\Web\\Http\\Controllers\\FileManagerController@tree',
    ]);
    $treeRoute->bind($tree);
    $tree->setRouteResolver(fn () => $treeRoute);

    $properties = Request::create('/web/files/3/properties', 'GET');
    $propertiesRoute = new Route(['GET'], '/web/files/{attId}/properties', [
        'livewire_component' => FilePropertiesPage::class,
    ]);
    $propertiesRoute->bind($properties);
    $propertiesRoute->setParameter('attId', '3');
    $properties->setRouteResolver(fn () => $propertiesRoute);

    expect(MenuActivePath::forRequest($tree))->toBe('/web/files/tree')
        ->and(MenuActivePath::forRequest($properties))->toBe('/web/files/3/properties');
});
