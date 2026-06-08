<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Livewire\Livewire;
use Velm\Admin\Pages\StoredViewPage;
use Velm\Admin\Support\ListPageSize;
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

test('stored view kanban groups board when arch defines group_by', function (): void {
    $env = app(Environment::class);
    patchPartnerKanbanArch($env, ['group_by' => 'active']);

    $board = Livewire::test(StoredViewPageProbe::class, [
        'module' => 'partners',
        'viewName' => 'partner.kanban',
    ])->instance()->kanbanBoard();

    expect($board['grouped'])->toBeTrue()
        ->and($board['group_by'])->toBe('active');
});

test('stored view kanban board adds open urls to grouped cards', function (): void {
    $env = app(Environment::class);
    patchPartnerKanbanArch($env, ['group_by' => 'active']);
    $partnerId = $env->model('res.partner')->create(['name' => 'Open Url Kanban', 'active' => true])->ids()[0];

    $board = Livewire::test(StoredViewPageProbe::class, [
        'module' => 'partners',
        'viewName' => 'partner.kanban',
    ])
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

test('stored view flat kanban paginates cards', function (): void {
    $env = app(Environment::class);

    for ($i = 1; $i <= 12; $i++) {
        $env->model('res.partner')->create(['name' => "Kanban Page {$i}", 'active' => true]);
    }

    $page = Livewire::test(StoredViewPage::class, [
        'module' => 'partners',
        'viewName' => 'partner.kanban',
    ])->set('listPerPage', 10);

    $board = $page->instance()->kanbanBoard();

    expect($board['grouped'])->toBeFalse()
        ->and($board['cards'])->toHaveCount(10)
        ->and($board['paginator'])->not->toBeNull()
        ->and($board['paginator']->total())->toBeGreaterThanOrEqual(12)
        ->and($board['paginator']->hasMorePages())->toBeTrue();

    $nextBoard = $page->call('nextPage')->instance()->kanbanBoard();

    expect($nextBoard['cards'])->toHaveCount($board['paginator']->total() - 10);
});

test('stored view flat kanban shows all cards when page size is all', function (): void {
    $env = app(Environment::class);

    for ($i = 1; $i <= 8; $i++) {
        $env->model('res.partner')->create(['name' => "Kanban All {$i}", 'active' => true]);
    }

    $board = Livewire::test(StoredViewPage::class, [
        'module' => 'partners',
        'viewName' => 'partner.kanban',
    ])
        ->set('listPerPage', ListPageSize::ALL)
        ->instance()
        ->kanbanBoard();

    expect($board['grouped'])->toBeFalse()
        ->and($board['paginator'])->not->toBeNull()
        ->and($board['paginator']->hasPages())->toBeFalse()
        ->and(count($board['cards']))->toBeGreaterThanOrEqual(8);
});

test('stored view grouped kanban does not paginate cards', function (): void {
    $env = app(Environment::class);
    patchPartnerKanbanArch($env, ['group_by' => 'active']);

    for ($i = 1; $i <= 12; $i++) {
        $env->model('res.partner')->create([
            'name' => "Grouped Kanban {$i}",
            'active' => $i % 2 === 0,
        ]);
    }

    $board = Livewire::test(StoredViewPageProbe::class, [
        'module' => 'partners',
        'viewName' => 'partner.kanban',
    ])
        ->set('listPerPage', 5)
        ->instance()
        ->kanbanBoard();

    $cardCount = collect($board['columns'])->sum(fn (array $column): int => count($column['cards']));

    expect($board['grouped'])->toBeTrue()
        ->and($board['paginator'] ?? null)->toBeNull()
        ->and($cardCount)->toBeGreaterThanOrEqual(12);
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
