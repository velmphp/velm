<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Livewire\Livewire;
use Velm\Admin\Pages\StoredViewPage;
use Velm\Admin\Support\StoredViewRoutes;
use Velm\Admin\Tests\Support\StoredViewPageProbe;
use Velm\Admin\Tests\TestCase;
use Velm\Environment;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }

    $this->actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']));
});

function patchPartnerKanbanArch(Environment $env, array $patch): void
{
    $env->withAclBypass(function () use ($env, $patch): void {
        $row = $env->model('ir.ui.view')->search([
            ['module', '=', 'partners'],
            ['name', '=', 'partner.kanban'],
        ])->read()[0];

        while ($row['inherit_id'] !== null) {
            $row = $env->browse('ir.ui.view', [(int) $row['inherit_id']])->read()[0];
        }

        $arch = json_decode((string) $row['arch'], true, flags: JSON_THROW_ON_ERROR);
        $env->browse('ir.ui.view', [(int) $row['id']])->write([
            'arch' => json_encode(array_replace_recursive($arch, $patch), JSON_THROW_ON_ERROR),
        ]);
    });
}

test('stored view kanban applies group_by from arch on mount', function (): void {
    $env = app(Environment::class);
    patchPartnerKanbanArch($env, ['group_by' => 'active']);

    $page = Livewire::test(StoredViewPageProbe::class, [
        'module' => 'partners',
        'viewName' => 'partner.kanban',
    ]);

    expect($page->instance()->listGroupBy)->toBe('active');
});

test('stored view kanban board adds open urls to grouped cards', function (): void {
    $env = app(Environment::class);
    $partnerId = $env->model('res.partner')->create(['name' => 'Open Url Kanban', 'active' => true])->ids()[0];

    $board = Livewire::test(StoredViewPage::class, [
        'module' => 'partners',
        'viewName' => 'partner.kanban',
    ])
        ->call('setListGroupBy', 'active')
        ->instance()
        ->kanbanBoard();

    expect($board['grouped'])->toBeTrue();

    $openUrls = [];
    foreach ($board['columns'] as $column) {
        foreach ($column['cards'] as $card) {
            $openUrls[] = $card['open_url'] ?? null;
        }
    }

    expect($openUrls)->toContain(StoredViewRoutes::recordPageUrl('partners', 'partner.form', $partnerId));
});

test('stored view kanban board adds open urls to flat cards', function (): void {
    $env = app(Environment::class);
    patchPartnerKanbanArch($env, ['group_by' => '']);
    $partnerId = $env->model('res.partner')->create(['name' => 'Flat Kanban', 'active' => true])->ids()[0];

    $board = Livewire::test(StoredViewPage::class, [
        'module' => 'partners',
        'viewName' => 'partner.kanban',
    ])->instance()->kanbanBoard();

    expect($board['grouped'])->toBeFalse()
        ->and(collect($board['cards'])->pluck('open_url'))->toContain(
            StoredViewRoutes::recordPageUrl('partners', 'partner.form', $partnerId),
        );
});

test('stored view kanban falls back to model headers without list view', function (): void {
    $env = app(Environment::class);
    $env->withAclBypass(function () use ($env): void {
        $view = $env->model('ir.ui.view')->search([
            ['module', '=', 'partners'],
            ['name', '=', 'partner.kanban'],
        ]);
        $row = $view->read()[0];
        $arch = json_decode((string) $row['arch'], true, flags: JSON_THROW_ON_ERROR);
        unset($arch['list_view']);
        $arch['group_by'] = '';
        $view->write(['arch' => json_encode($arch, JSON_THROW_ON_ERROR)]);
    });

    $page = Livewire::test(StoredViewPage::class, [
        'module' => 'partners',
        'viewName' => 'partner.kanban',
    ])->instance();

    $queryArch = (new ReflectionMethod(StoredViewPage::class, 'kanbanQueryArch'))->invoke($page);
    $headers = (new ReflectionMethod(StoredViewPage::class, 'kanbanToolbarHeaders'))->invoke($page);

    expect($queryArch['model'])->toBe('res.partner')
        ->and($queryArch['fields'])->not->toBeEmpty()
        ->and($headers)->not->toBeEmpty();
});

test('stored view pivot grid and list view url resolve for analytics views', function (): void {
    $page = Livewire::test(StoredViewPage::class, [
        'module' => 'partners',
        'viewName' => 'partner.pivot',
    ])->instance();

    $grid = $page->pivotGrid();
    $listViewUrl = (new ReflectionMethod(StoredViewPage::class, 'listViewUrl'))->invoke($page);

    expect($grid)->toBeArray()->not->toBeEmpty()
        ->and($listViewUrl)->toContain('/velm/views/partners/partner.list');
});
