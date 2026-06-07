<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Velm\Admin\Pages\StoredViewCreatePage;
use Velm\Admin\Support\MenuLinkResolver;
use Velm\Admin\Support\StoredViewRoutes;
use Velm\Admin\Tests\TestCase;

uses(TestCase::class);

test('stored view routes parse canonical menu hrefs', function (): void {
    expect(StoredViewRoutes::parseListHref('/velm/views/demo_relations/project.list'))
        ->toBe(['module' => 'demo_relations', 'view' => 'project.list'])
        ->and(StoredViewRoutes::listViewFromFormView('project.form'))
        ->toBe('project.list')
        ->and(StoredViewRoutes::listViewFromRecordView('project.detail'))
        ->toBe('project.list')
        ->and(StoredViewRoutes::listHref('demo_relations', 'project.list'))
        ->toBe('/velm/views/demo_relations/project.list');
});

test('stored view routes expose analytics href helpers', function (): void {
    expect(StoredViewRoutes::viewHref('workflow', 'task.kanban'))
        ->toBe('/velm/views/workflow/task.kanban')
        ->and(StoredViewRoutes::parseViewHref('/velm/views/workflow/task.graph'))
        ->toBe(['module' => 'workflow', 'view' => 'task.graph'])
        ->and(StoredViewRoutes::siblingListView('workflow', 'task.kanban'))
        ->toBe('task.list');
});

test('menu link resolver maps unknown view hrefs to stored view list page', function (): void {
    $url = MenuLinkResolver::url('/velm/views/demo_relations/project.list');

    expect($url)->toContain('/velm/views/demo_relations/project.list');
});

test('create page url resolves to the create livewire page not the record page', function (): void {
    $url = StoredViewRoutes::createPageUrl('demo_relations', 'project.form');
    $route = Route::getRoutes()->match(Request::create($url, 'GET'));

    expect($route->getAction('livewire_component'))->toBe(StoredViewCreatePage::class);
});

test('stored view routes reject empty or invalid list hrefs', function (): void {
    expect(StoredViewRoutes::parseListHref(null))->toBeNull()
        ->and(StoredViewRoutes::parseListHref(''))->toBeNull()
        ->and(StoredViewRoutes::parseListHref('/not/a/stored/view'))->toBeNull();
});

test('stored view routes expose edit page url and sibling list view from arch', function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }

    $url = StoredViewRoutes::editPageUrl('partners', 'partner.form', 15);

    expect($url)->toContain('partners')
        ->and($url)->toContain('partner.form')
        ->and($url)->toContain('15')
        ->and(StoredViewRoutes::siblingListView('partners', 'partner.graph'))->toBe('partner.list');
});

test('record view from form view keeps form name when detail view is missing', function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }

    expect(StoredViewRoutes::recordViewFromFormView('partners', 'custom.form'))->toBe('custom.form');
});
