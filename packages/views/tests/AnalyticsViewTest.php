<?php

declare(strict_types=1);

use Velm\Views\Arch\ArchNormalizer;
use Velm\Views\Authoring\Card;
use Velm\Views\Authoring\Field;
use Velm\Views\Authoring\GraphView;
use Velm\Views\Authoring\KanbanView;
use Velm\Views\Authoring\PivotView;

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
        ->toContain('kanban', 'graph', 'pivot');
});
