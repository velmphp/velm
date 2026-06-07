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
