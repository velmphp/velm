<?php

declare(strict_types=1);

use Velm\Views\Arch\ArchNormalizer;
use Velm\Views\Authoring\Card;
use Velm\Views\Authoring\DashboardView;
use Velm\Views\Authoring\Field;
use Velm\Views\Authoring\GraphView;
use Velm\Views\Authoring\KanbanView;
use Velm\Views\Authoring\PivotView;
use Velm\Views\Authoring\Widgets\ChartWidget;
use Velm\Views\Authoring\Widgets\StatWidget;
use Velm\Views\Authoring\Widgets\TableWidget;

test('kanban view declaration exposes route arch schema', function (): void {
    $view = KanbanView::make('task.kanban')
        ->model('workflow.task')
        ->title('Tasks')
        ->groupBy('state')
        ->card(
            Card::make()
                ->title('name')
                ->subtitle('priority')
                ->fields(['user_id'])
                ->badges([Field::make('active')->toggle()])
        )
        ->formView('task.form')
        ->listView('task.list')
        ->toArray();

    expect($view['view_type'])->toBe('kanban')
        ->and($view['arch']['group_by'])->toBe('state')
        ->and($view['arch']['form_view'])->toBe('task.form')
        ->and($view['arch']['card']['title'])->toBe('name')
        ->and($view['arch']['card']['badges'][0]['name'])->toBe('active');
});

test('graph view declaration exposes route arch schema', function (): void {
    $view = GraphView::make('lead.graph')
        ->model('crm.lead')
        ->title('Leads')
        ->groupBy('stage_id')
        ->measures(['expected_revenue:sum', '__count'])
        ->chart('bar')
        ->domain([['active', '=', true]])
        ->listView('lead.list')
        ->toArray();

    expect($view['view_type'])->toBe('graph')
        ->and($view['arch']['group_by'])->toBe('stage_id')
        ->and($view['arch']['measures'])->toBe(['expected_revenue:sum', '__count'])
        ->and($view['arch']['chart'])->toBe('bar')
        ->and($view['arch']['domain'])->toBe([['active', '=', true]])
        ->and($view['arch']['title'])->toBe('Leads')
        ->and($view['arch']['list_view'])->toBe('lead.list');
});

test('graph view requires model groupBy and measure before serialize', function (): void {
    GraphView::make('incomplete.graph')->toArray();
})->throws(LogicException::class, 'missing model()');

test('pivot view declaration exposes route arch schema', function (): void {
    $view = PivotView::make('lead.pivot')
        ->model('crm.lead')
        ->rows(['stage_id', 'user_id'])
        ->cols(['country_id'])
        ->measures(['__count', 'expected_revenue:sum'])
        ->toArray();

    expect($view['view_type'])->toBe('pivot')
        ->and($view['arch']['rows'])->toBe(['stage_id', 'user_id'])
        ->and($view['arch']['cols'])->toBe(['country_id'])
        ->and($view['arch']['measures'])->toBe(['__count', 'expected_revenue:sum']);
});

test('arch normalizer coerces analytics arch keys', function (): void {
    $kanban = ArchNormalizer::normalize([
        'group_by' => 'state',
        'card' => [
            'title' => 'name',
            'fields' => ['priority'],
            'badges' => ['active'],
        ],
    ], 'kanban');

    $graph = ArchNormalizer::normalize([
        'group_by' => 'stage_id',
        'measure' => 'amount:sum',
    ], 'graph');

    $pivot = ArchNormalizer::normalize([
        'rows' => 'stage_id',
        'cols' => 'country_id',
    ], 'pivot');

    expect($kanban['card']['fields'][0])->toBe(['name' => 'priority'])
        ->and($graph['measures'])->toBe(['amount:sum'])
        ->and($graph['chart'])->toBe('bar')
        ->and($pivot['rows'])->toBe(['stage_id'])
        ->and($pivot['cols'])->toBe(['country_id'])
        ->and($pivot['measures'])->toBe(['__count']);
});

test('supported view types include analytics renderers', function (): void {
    expect(ArchNormalizer::supportedViewTypes())
        ->toContain('kanban', 'graph', 'pivot', 'dashboard');
});

test('dashboard view declaration exposes widget arch schema', function (): void {
    $view = DashboardView::make('partner.dashboard')
        ->model('res.partner')
        ->title('Partners overview')
        ->columns(2)
        ->listView('partner.list')
        ->widgets([
            StatWidget::make('total')->title('Total contacts'),
            StatWidget::make('companies')->domain([['is_company', '=', true]]),
            TableWidget::make('recent')->view('partner.list')->limit(3)->colspan('full'),
            ChartWidget::make('by_country')->view('partner.graph'),
        ])
        ->toArray();

    expect($view['view_type'])->toBe('dashboard')
        ->and($view['arch']['columns'])->toBe(2)
        ->and($view['arch']['list_view'])->toBe('partner.list')
        ->and($view['arch']['widgets'])->toHaveCount(4)
        ->and($view['arch']['widgets'][0]['type'])->toBe('stat')
        ->and($view['arch']['widgets'][2]['limit'])->toBe(3)
        ->and($view['arch']['widgets'][2]['colspan'])->toBe('full');
});

test('dashboard view requires model and widgets before serialize', function (): void {
    DashboardView::make('incomplete.dashboard')->toArray();
})->throws(LogicException::class, 'missing model()');

test('arch normalizer coerces dashboard widget specs', function (): void {
    $dashboard = ArchNormalizer::normalize([
        'columns' => 0,
        'widgets' => [
            ['type' => 'stat', 'id' => 'total', 'limit' => 2],
            ['type' => 'table', 'id' => 'recent', 'view' => 'partner.list', 'limit' => 0],
            ['ignored' => true],
        ],
    ], 'dashboard');

    expect($dashboard['columns'])->toBe(1)
        ->and($dashboard['widgets'])->toHaveCount(2)
        ->and($dashboard['widgets'][0]['colspan'])->toBe(1)
        ->and($dashboard['widgets'][1]['limit'])->toBe(1);
});

test('arch normalizer migrates legacy dashboard widget size full to colspan', function (): void {
    $dashboard = ArchNormalizer::normalize([
        'widgets' => [
            ['type' => 'chart', 'id' => 'wide', 'view' => 'partner.graph', 'size' => 'full'],
        ],
    ], 'dashboard');

    expect($dashboard['widgets'][0]['colspan'])->toBe('full');
});

test('dashboard widget colspan rejects values below one', function (): void {
    StatWidget::make('total')->colspan(0);
})->throws(InvalidArgumentException::class, 'colspan must be at least 1');

test('dashboard widgets serialize numeric and full colspan values', function (): void {
    expect(StatWidget::make('half')->colspan(1)->toArray()['colspan'])->toBe(1)
        ->and(TableWidget::make('wide')->view('partner.list')->colspan('full')->toArray()['colspan'])->toBe('full')
        ->and(ChartWidget::make('chart')->view('partner.graph')->colspan(2)->toArray()['colspan'])->toBe(2);
});
