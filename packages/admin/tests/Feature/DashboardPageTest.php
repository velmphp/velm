<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Livewire\Livewire;
use Velm\Admin\Dashboard\DashboardService;
use Velm\Admin\Pages\DashboardPage;
use Velm\Admin\Tests\TestCase;
use Velm\Environment;
use Velm\Views\Menu\MenuLayout;
use Velm\Views\Menu\MenuLayoutContext;
use Velm\Views\Menu\MenuTreeBuilder;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('authenticated dashboard page renders partner widget', function (): void {
    $this->actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']));

    Livewire::test(DashboardPage::class)
        ->assertOk()
        ->assertSee('Dashboard', false)
        ->assertSee('Contacts', false);
});

test('panel home redirects to dashboard', function (): void {
    $this->actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']));

    $this->get('/velm')
        ->assertRedirect('/velm/dashboard');
});

test('dashboard route uses standard apps menu layout without highlighting an app', function (): void {
    $env = app(Environment::class);
    $panel = trim((string) config('velm.panel_path', 'velm'), '/');
    $tree = (new MenuTreeBuilder)->build($env, '/'.$panel.'/dashboard');
    $menu = MenuLayoutContext::forTree($tree, '/'.$panel.'/dashboard', MenuLayout::APPS);

    expect($menu['menu_layout'])->toBe(MenuLayout::APPS)
        ->and($menu['menu'])->not->toBeEmpty()
        ->and($menu['menu_roots'])->not->toBeEmpty()
        ->and($menu['menu_active_root'])->toBeNull()
        ->and($menu['menu_active_root_index'])->toBeNull();
});

test('dashboard service hides widgets when model access is denied', function (): void {
    $env = app(Environment::class);
    /** @var list<string> $roots */
    $roots = config('velm.addon_paths', []);

    $service = new DashboardService;
    $all = $service->visibleWidgets($env, $roots);
    $ids = collect($all)->pluck('id')->all();

    expect($ids)->toContain('partners_summary');

    $limited = new Environment(
        $env->connection,
        $env->registry,
        999,
        $env->context,
    );

    $limitedWidgets = $service->visibleWidgets($limited, $roots);

    expect(collect($limitedWidgets)->pluck('id')->all())->toBe([]);
});
