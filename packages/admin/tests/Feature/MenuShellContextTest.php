<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Velm\Admin\Support\MenuActivePath;
use Velm\Admin\Tests\TestCase;
use Velm\Views\Menu\MenuLayout;
use Velm\Views\Menu\MenuTreeBuilder;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('menu tree builder marks partner list active for canonical path', function (): void {
    $env = app(\Velm\Environment::class);
    $tree = app(MenuTreeBuilder::class)->build(
        $env,
        '/velm/views/partners/partner.list',
    );

    expect($tree)->not->toBeEmpty();

    $contacts = collect($tree)->firstWhere('label', 'Contacts');

    expect($contacts)->not->toBeNull()
        ->and($contacts['active'] ?? false)->toBeTrue();
});

test('menu layout context builds apps secondary for partners', function (): void {
    $env = app(\Velm\Environment::class);
    $tree = app(MenuTreeBuilder::class)->build(
        $env,
        '/velm/views/partners/partner.list',
    );

    $ctx = \Velm\Views\Menu\MenuLayoutContext::forTree(
        $tree,
        '/velm/views/partners/partner.list',
        MenuLayout::APPS,
        $env,
    );

    expect($ctx['menu_secondary'])->toHaveCount(4);

    $labels = array_column($ctx['menu_secondary'], 'label');
    expect($labels)->toContain('Partners', 'Partners dashboard', 'Partners graph', 'Partners pivot');

    $contactsRoot = collect($ctx['menu_roots'])->firstWhere('label', 'Contacts');
    expect($contactsRoot['nav_href'] ?? null)->toBe('/velm/views/partners/partner.dashboard');
});

test('menu layout config defaults to apps', function (): void {
    expect(config('velm.menu_layout'))->toBe(MenuLayout::APPS);
});

test('module workspace sidebar links to apps catalog', function (): void {
    $this->actingAs(new \Illuminate\Auth\GenericUser([
        'id' => 1,
        'name' => 'Admin',
        'email' => 'admin@test',
    ]));

    $this->get('/velm/views/partners/partner.list')
        ->assertOk()
        ->assertSee(__('Apps'), false);
});

test('partner list route resolves contacts as active menu root', function (): void {
    $request = Request::create('/velm/views/partners/partner.list', 'GET');
    $route = app('router')->getRoutes()->match($request);
    $request->setRouteResolver(fn () => $route);

    $currentPath = MenuActivePath::forRequest($request);
    $tree = app(MenuTreeBuilder::class)->build(app(\Velm\Environment::class), $currentPath);
    [$root, $index] = MenuTreeBuilder::activeRoot($tree, $currentPath);

    expect($currentPath)->toBe('/velm/views/partners/partner.list')
        ->and($root['label'] ?? null)->toBe('Contacts')
        ->and($index)->toBeGreaterThanOrEqual(0);
});
