<?php

declare(strict_types=1);

use Velm\Admin\Arch\DashboardBoardBuilder;
use Velm\Admin\Arch\GraphDataBuilder;
use Velm\Admin\Arch\KanbanBoardBuilder;
use Velm\Admin\Arch\ListQuery;
use Velm\Admin\Arch\PivotGridBuilder;
use Velm\Admin\Tests\TestCase;
use Velm\Views\Authoring\Card;
use Velm\Views\Authoring\GraphView;
use Velm\Views\Authoring\KanbanView;
use Velm\Views\Authoring\PivotView;
use Velm\Views\ViewRegistry;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('kanban board builder groups partner records when group by is set', function (): void {
    $env = app(\Velm\Environment::class);
    $env->model('res.partner')->create(['name' => 'Active Co', 'active' => true]);
    $env->model('res.partner')->create(['name' => 'Inactive Co', 'active' => false]);

    $arch = (new ViewRegistry)->arch($env, 'partners', 'partner.kanban');
    $arch['group_by'] = 'active';
    $queryArch = (new ViewRegistry)->arch($env, 'partners', 'partner.list');
    $board = (new KanbanBoardBuilder)->build($arch, $env, $queryArch, new ListQuery, 'active');

    expect($board['grouped'])->toBeTrue()
        ->and($board['group_by'])->toBe('active')
        ->and($board['columns'])->toHaveCount(2);

    $labels = array_column($board['columns'], 'label');
    expect($labels)->toContain('Yes', 'No');

    $allTitles = [];
    foreach ($board['columns'] as $column) {
        foreach ($column['cards'] as $card) {
            $allTitles[] = $card['title'];
        }
    }

    expect($allTitles)->toContain('Active Co', 'Inactive Co');
});

test('kanban board builder returns flat cards when not grouped', function (): void {
    $env = app(\Velm\Environment::class);
    $env->model('res.partner')->create(['name' => 'Flat Partner', 'active' => true]);

    $arch = (new ViewRegistry)->arch($env, 'partners', 'partner.kanban');
    $queryArch = (new ViewRegistry)->arch($env, 'partners', 'partner.list');
    $board = (new KanbanBoardBuilder)->build($arch, $env, $queryArch);

    expect($board['grouped'])->toBeFalse()
        ->and($board['columns'])->toBe([])
        ->and(collect($board['cards'])->pluck('title'))->toContain('Flat Partner');
});

test('graph data builder aggregates partners by country', function (): void {
    $env = app(\Velm\Environment::class);
    $be = $env->model('res.country')->create(['name' => 'Belgium', 'code' => 'BE']);
    $nl = $env->model('res.country')->create(['name' => 'Netherlands', 'code' => 'NL']);

    $env->model('res.partner')->create(['name' => 'Graph Velm BE', 'country_id' => $be->ids()[0]]);
    $env->model('res.partner')->create(['name' => 'Graph Velm BE 2', 'country_id' => $be->ids()[0]]);
    $env->model('res.partner')->create(['name' => 'Graph Velm NL', 'country_id' => $nl->ids()[0]]);

    $arch = GraphView::make('partner.graph')
        ->model('res.partner')
        ->groupBy('country_id')
        ->measure('__count')
        ->domain([['name', 'ilike', 'Graph %']])
        ->toArray();

    $arch = [
        'view_type' => $arch['view_type'],
        'model' => $arch['model'],
        ...$arch['arch'],
    ];

    $graph = (new GraphDataBuilder)->build($arch, $env);

    expect($graph['points'])->toHaveCount(2);

    $byLabel = collect($graph['points'])->keyBy('label');
    expect($byLabel['Belgium']['value'])->toEqual(2)
        ->and($byLabel['Netherlands']['value'])->toEqual(1);
});

test('pivot grid builder crosses company and active dimensions', function (): void {
    $env = app(\Velm\Environment::class);

    $env->model('res.partner')->create(['name' => 'A', 'is_company' => true, 'active' => true]);
    $env->model('res.partner')->create(['name' => 'B', 'is_company' => true, 'active' => false]);
    $env->model('res.partner')->create(['name' => 'C', 'is_company' => false, 'active' => true]);

    $arch = PivotView::make('partner.pivot')
        ->model('res.partner')
        ->rows(['is_company'])
        ->cols(['active'])
        ->measures(['__count'])
        ->toArray();

    $arch = [
        'view_type' => $arch['view_type'],
        'model' => $arch['model'],
        ...$arch['arch'],
    ];

    $grid = (new PivotGridBuilder)->build($arch, $env);

    expect($grid['row_headers'])->toHaveCount(2)
        ->and($grid['col_headers'])->toHaveCount(2)
        ->and($grid['matrix'])->not->toBeEmpty();
});

test('kanban board builder returns static domain when query domain is empty', function (): void {
    $env = app(\Velm\Environment::class);
    $env->model('res.partner')->create(['name' => 'Static Only', 'active' => true]);

    $arch = (new ViewRegistry)->arch($env, 'partners', 'partner.kanban');
    $arch['domain'] = [['active', '=', true]];
    $queryArch = (new ViewRegistry)->arch($env, 'partners', 'partner.list');

    $board = (new KanbanBoardBuilder)->build($arch, $env, $queryArch);

    expect(collect($board['cards'])->pluck('title'))->toContain('Static Only');
});

test('kanban board builder merges static and dynamic domains', function (): void {
    $env = app(\Velm\Environment::class);
    $env->model('res.partner')->create(['name' => 'Domain Merge', 'active' => true]);

    $arch = (new ViewRegistry)->arch($env, 'partners', 'partner.kanban');
    $arch['domain'] = [['active', '=', true]];
    $queryArch = (new ViewRegistry)->arch($env, 'partners', 'partner.list');

    $board = (new KanbanBoardBuilder)->build($arch, $env, $queryArch, new ListQuery(search: 'Domain'));

    expect(collect($board['cards'])->pluck('title'))->toContain('Domain Merge');
});

test('kanban board builder skips invalid card field specs and labels unknown fields', function (): void {
    $env = app(\Velm\Environment::class);
    $env->model('res.partner')->create(['name' => 'Card Spec', 'active' => true]);

    $arch = (new ViewRegistry)->arch($env, 'partners', 'partner.kanban');
    $arch['card'] = [
        'title' => 'name',
        'fields' => ['not-an-array', ['name' => ''], ['name' => 'custom_label']],
        'badges' => [['name' => 'active', 'widget' => 'toggle']],
    ];
    $queryArch = (new ViewRegistry)->arch($env, 'partners', 'partner.list');

    $board = (new KanbanBoardBuilder)->build($arch, $env, $queryArch);
    $card = $board['cards'][0];

    expect(collect($card['fields'])->pluck('label'))->toContain('Custom label')
        ->and($card['badges'][0]['kind'])->toBe('toggle');
});

test('kanban board builder builds many2one group domains', function (): void {
    $env = app(\Velm\Environment::class);
    $country = $env->model('res.country')->create(['name' => 'Grouped Country', 'code' => 'GC']);
    $env->model('res.partner')->create([
        'name' => 'Country Card',
        'country_id' => $country->ids()[0],
    ]);

    $arch = (new ViewRegistry)->arch($env, 'partners', 'partner.kanban');
    $queryArch = (new ViewRegistry)->arch($env, 'partners', 'partner.list');
    $board = (new KanbanBoardBuilder)->build($arch, $env, $queryArch, new ListQuery, 'country_id');

    $domains = array_column($board['columns'], 'domain');

    expect($board['grouped'])->toBeTrue()
        ->and($domains)->toContain([['country_id', '=', $country->ids()[0]]]);
});

test('kanban view declaration card schema feeds board builder', function (): void {
    $arch = KanbanView::make('partner.kanban')
        ->model('res.partner')
        ->groupBy('active')
        ->card(Card::make()->title('name')->subtitle('country_id'))
        ->toArray();

    expect($arch['arch']['card']['title'])->toBe('name');
});

test('dashboard board builder resolves stat table and chart widgets', function (): void {
    $env = app(\Velm\Environment::class);
    $country = $env->model('res.country')->create(['name' => 'Board Country', 'code' => 'BC']);
    $env->model('res.partner')->create([
        'name' => 'Board Partner',
        'is_company' => true,
        'country_id' => $country->ids()[0],
    ]);

    $arch = (new ViewRegistry)->arch($env, 'partners', 'partner.dashboard');
    $board = (new DashboardBoardBuilder)->build($arch, $env, 'partners');

    expect($board['columns'])->toBe(2)
        ->and($board['widgets'])->toHaveCount(4)
        ->and($board['widgets'][0]['view'])->toBe('velm-ui::dashboard.stat-card')
        ->and($board['widgets'][0]['data']['value'])->toBeGreaterThanOrEqual(1)
        ->and(collect($board['widgets'][2]['data']['items'])->pluck('label'))->toContain('Board Partner')
        ->and($board['widgets'][3]['data']['points'])->not->toBeEmpty();
});
