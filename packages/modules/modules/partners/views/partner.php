<?php

declare(strict_types=1);

use Velm\Views\Authoring\Action;
use Velm\Views\Authoring\ActionForm;
use Velm\Views\Authoring\ActionVariant;
use Velm\Views\Authoring\Card;
use Velm\Views\Authoring\DashboardView;
use Velm\Views\Authoring\DetailView;
use Velm\Views\Authoring\Field;
use Velm\Views\Authoring\FormView;
use Velm\Views\Authoring\GraphView;
use Velm\Views\Authoring\KanbanView;
use Velm\Views\Authoring\ListRowAction;
use Velm\Views\Authoring\ListView;
use Velm\Views\Authoring\Menus;
use Velm\Views\Authoring\PivotView;
use Velm\Views\Authoring\Widgets\ChartWidget;
use Velm\Views\Authoring\Widgets\StatWidget;
use Velm\Views\Authoring\Widgets\TableWidget;
use Velm\Views\Data\ViewsData;

$m = new Menus('partners');

return ViewsData::make()
    ->views(
        ListView::make('partner.list')
            ->model('res.partner')
            ->title('Partners')
            ->formView('partner.form')
            ->detailView('partner.detail')
            ->pageActions([
                Action::make('Quick add')
                    ->model('res.partner')
                    ->variant(ActionVariant::Primary)
                    ->perm('create')
                    ->form(fn (ActionForm $form) => $form
                        ->section('identity', 'Quick contact', [
                            'name',
                            'country_id',
                            Field::make('active')->toggle(),
                        ])
                    ),
                Action::make('Load demo data')
                    ->url('/web/demo/partners/seed')
                    ->confirm('Load or refresh the bundled demo partners?')
                    ->variant(ActionVariant::Warning)
                    ->perm('create'),
                Action::make('Export CSV')
                    ->url('/web/demo/partners/export')
                    ->method('GET')
                    ->variant(ActionVariant::Secondary)
                    ->perm('read'),
            ])
            ->bulkActions([
                Action::make('Export selected')
                    ->url('/web/demo/partners/export')
                    ->method('GET')
                    ->variant(ActionVariant::Secondary)
                    ->perm('read'),
            ])
            ->rowActions([
                ListRowAction::open(),
                ListRowAction::edit(),
            ])
            ->columns([
                'name',
                Field::make('is_company')->toggle(),
                Field::make('company_id'),
                'country_id',
                Field::make('active')->toggle(),
            ]),
        DetailView::make('partner.detail')
            ->model('res.partner')
            ->title('Partner')
            ->headerActions([
                Action::make('Quick edit')
                    ->model('res.partner')
                    ->variant(ActionVariant::Danger)
                    ->perm('write')
                    ->form(fn (ActionForm $form) => $form->cols(2)
                        ->section('identity', 'Quick edit', [
                            'name',
                            'country_id',
                            Field::make('active')->toggle(),
                        ])
                    ),
                Action::make('Duplicate')
                    ->url('/web/demo/partners/{id}/duplicate')
                    ->confirm('Create a copy of this partner?')
                    ->variant(ActionVariant::Success)
                    ->perm('create'),
                Action::make('Export JSON')
                    ->url('/web/demo/partners/{id}/export')
                    ->method('GET')
                    ->variant(ActionVariant::Secondary)
                    ->perm('read'),
            ])
            ->section('identity', 'Identity', [
                'name',
                Field::make('is_company')->toggle(),
                Field::make('active')->toggle(),
            ])
            ->section('organization', 'Organization', ['company_id'])
            ->section('address', 'Address', ['country_id']),
        FormView::make('partner.form')
            ->model('res.partner')
            ->section('identity', 'Identity', [
                'name',
                Field::make('is_company')->toggle(),
                Field::make('active')->toggle(),
            ])
            ->section('organization', 'Organization', ['company_id'])
            ->section('address', 'Address', ['country_id']),
        KanbanView::make('partner.kanban')
            ->model('res.partner')
            ->title('Partners')
            ->card(
                Card::make()
                    ->title('name')
                    ->subtitle('country_id')
                    ->fields(['is_company'])
                    ->badges([Field::make('active')->toggle()])
            )
            ->formView('partner.form')
            ->listView('partner.list'),
        GraphView::make('partner.graph')
            ->model('res.partner')
            ->title('Partners by country')
            ->groupBy('country_id')
            ->measure('__count')
            ->chart('bar')
            ->listView('partner.list'),
        PivotView::make('partner.pivot')
            ->model('res.partner')
            ->title('Partner matrix')
            ->rows(['is_company'])
            ->cols(['active'])
            ->measures(['__count'])
            ->listView('partner.list'),
        DashboardView::make('partner.dashboard')
            ->model('res.partner')
            ->title('Partners overview')
            ->columns(2)
            ->listView('partner.list')
            ->widgets([
                StatWidget::make('total')
                    ->title('Total contacts')
                    ->icon('heroicon-o-user-group'),
                StatWidget::make('companies')
                    ->title('Companies')
                    ->domain([['is_company', '=', true]])
                    ->icon('heroicon-o-building-office'),
                TableWidget::make('recent')
                    ->title('Recent contacts')
                    ->view('partner.list')
                    ->limit(5)
                    ->icon('heroicon-o-clock'),
                ChartWidget::make('by_country')
                    ->title('By country')
                    ->view('partner.graph')
                    ->icon('heroicon-o-chart-bar'),
            ]),
    )
    ->menus(
        $m->group('contacts', 'Contacts')
            ->icon('user-group')
            ->sequence(10)
            ->children(
                $m->item('partners', 'Partners')
                    ->view('partner.list')
                    ->icon('user-group')
                    ->sequence(10),
                $m->item('partners_dashboard', 'Partners dashboard')
                    ->view('partner.dashboard')
                    ->icon('squares-2x2')
                    ->sequence(20),
                $m->item('partners_graph', 'Partners graph')
                    ->view('partner.graph')
                    ->icon('chart-bar')
                    ->sequence(30),
                $m->item('partners_pivot', 'Partners pivot')
                    ->view('partner.pivot')
                    ->icon('table-cells')
                    ->sequence(40),
            ),
    );
