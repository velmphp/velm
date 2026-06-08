<?php

declare(strict_types=1);

use Velm\Modules\ModuleInstaller;
use Velm\Views\Arch\ViewActionLocator;
use Velm\Views\Authoring\Action;
use Velm\Views\Authoring\ActionForm;
use Velm\Views\Authoring\DashboardView;
use Velm\Views\Authoring\FormView;
use Velm\Views\Authoring\Widgets\ChartWidget;
use Velm\Views\Authoring\Widgets\StatWidget;
use Velm\Views\Authoring\Widgets\TableWidget;
use Velm\Views\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('action form serializes optional model metadata', function (): void {
    $arch = ActionForm::make()
        ->model('res.partner')
        ->section('main', 'Main', ['name'])
        ->toArray();

    expect($arch['model'])->toBe('res.partner')
        ->and($arch['sections'])->toHaveCount(1);
});

test('form view serializes header actions and requires model', function (): void {
    $form = FormView::make('partner.form')
        ->model('res.partner')
        ->headerActions([
            Action::make('Export')->url('/web/partners/export'),
        ])
        ->section('main', 'Main', ['name'])
        ->toArray();

    expect($form['arch']['header_actions'])->toHaveCount(1)
        ->and($form['view_type'])->toBe('form');

    FormView::make('missing.model')->toArray();
})->throws(LogicException::class, 'missing model()');

test('dashboard view requires model and widgets', function (): void {
    DashboardView::make('board.only-model')->model('res.partner')->toArray();
})->throws(LogicException::class, 'missing widgets()');

test('dashboard view serializes title domain and list view metadata', function (): void {
    $view = DashboardView::make('partner.dashboard')
        ->model('res.partner')
        ->title('Overview')
        ->columns(3)
        ->domain([['active', '=', true]])
        ->listView('partner.list')
        ->widgets([
            StatWidget::make('total')->title('Total')->model('res.partner')->measure('__count')->icon('chart'),
            TableWidget::make('recent')->title('Recent')->view('partner.list')->limit(3),
            ChartWidget::make('chart')->title('Chart')->view('partner.graph'),
        ])
        ->toArray();

    expect($view['arch']['title'])->toBe('Overview')
        ->and($view['arch']['columns'])->toBe(3)
        ->and($view['arch']['domain'])->toBe([['active', '=', true]])
        ->and($view['arch']['list_view'])->toBe('partner.list')
        ->and($view['arch']['widgets'][0]['measure'])->toBe('__count')
        ->and($view['arch']['widgets'][1]['limit'])->toBe(3);
});

test('view action locator resolves header aliases and ignores invalid actions', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'partners']);
    $env = $installer->environment($roots);
    $view = $env->model('ir.ui.view')->search([
        ['module', '=', 'partners'],
        ['name', '=', 'partner.detail'],
    ]);
    $row = $view->read()[0];
    $arch = json_decode((string) $row['arch'], true, flags: JSON_THROW_ON_ERROR);
    $arch['header_actions'] = [
        ['label' => 'Header act', 'url' => '/web/demo'],
        ['label' => '', 'url' => '/ignored'],
        'not-an-array',
    ];
    $arch['page_actions'] = 'invalid';
    $view->write(['arch' => json_encode($arch, JSON_THROW_ON_ERROR)]);

    $locator = new ViewActionLocator;

    expect($locator->find($env, 'partners', 'partner.detail', 'header', 'header-act')['url'])->toBe('/web/demo')
        ->and($locator->find($env, 'partners', 'partner.detail', 'header_actions', 'header-act'))->not->toBeNull()
        ->and($locator->find($env, 'partners', 'partner.detail', 'bulk', 'missing'))->toBeNull()
        ->and($locator->find($env, 'partners', 'partner.detail', 'page', 'missing'))->toBeNull();
});

test('dashboard widgets serialize optional stat fields', function (): void {
    expect(StatWidget::make('companies')
        ->title('Companies')
        ->model('res.partner')
        ->domain([['is_company', '=', true]])
        ->measure('__count')
        ->icon('building')
        ->toArray())->toMatchArray([
            'type' => 'stat',
            'id' => 'companies',
            'title' => 'Companies',
            'model' => 'res.partner',
            'domain' => [['is_company', '=', true]],
            'measure' => '__count',
            'icon' => 'building',
        ]);
});

test('table widget requires view before serialize', function (): void {
    TableWidget::make('broken')->title('Broken')->toArray();
})->throws(LogicException::class, 'missing view()');

test('chart widget requires view before serialize', function (): void {
    ChartWidget::make('broken')->title('Broken')->toArray();
})->throws(LogicException::class, 'missing view()');

test('dashboard widgets serialize colspan icon and reject invalid colspan', function (): void {
    expect(TableWidget::make('wide')
        ->title('Wide')
        ->view('partner.list')
        ->colspan('full')
        ->icon('table')
        ->toArray())->toMatchArray([
            'type' => 'table',
            'colspan' => 'full',
            'icon' => 'table',
        ])
        ->and(ChartWidget::make('wide')
            ->title('Wide')
            ->view('partner.graph')
            ->colspan(2)
            ->icon('chart')
            ->toArray())->toMatchArray([
                'type' => 'chart',
                'colspan' => 2,
                'icon' => 'chart',
            ]);

    ChartWidget::make('invalid')->view('partner.graph')->colspan(0);
})->throws(InvalidArgumentException::class, 'colspan must be at least 1');

test('table widget colspan accepts integers and rejects invalid values', function (): void {
    expect(TableWidget::make('table')
        ->view('partner.list')
        ->colspan(3)
        ->toArray()['colspan'])->toBe(3);

    TableWidget::make('invalid')->view('partner.list')->colspan(0);
})->throws(InvalidArgumentException::class, 'colspan must be at least 1');

test('view action locator ignores invalid page action entries', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'partners']);
    $env = $installer->environment($roots);
    $view = $env->model('ir.ui.view')->search([
        ['module', '=', 'partners'],
        ['name', '=', 'partner.list'],
    ]);
    $row = $view->read()[0];
    $arch = json_decode((string) $row['arch'], true, flags: JSON_THROW_ON_ERROR);
    $arch['page_actions'] = [
        ['label' => 'Valid act', 'url' => '/web/demo'],
        ['label' => '', 'url' => '/ignored'],
        'not-an-array',
    ];
    $view->write(['arch' => json_encode($arch, JSON_THROW_ON_ERROR)]);

    $locator = new ViewActionLocator;

    expect($locator->find($env, 'partners', 'partner.list', 'page', 'valid-act')['url'])->toBe('/web/demo')
        ->and($locator->find($env, 'partners', 'partner.list', 'page_actions', 'valid-act'))->not->toBeNull()
        ->and($locator->find($env, 'partners', 'partner.list', 'bulk', 'valid-act'))->toBeNull()
        ->and($locator->find($env, 'partners', 'partner.list', 'bulk_actions', 'valid-act'))->toBeNull();
});

test('view action locator ignores malformed header action entries', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'partners']);
    $env = $installer->environment($roots);
    $view = $env->model('ir.ui.view')->search([
        ['module', '=', 'partners'],
        ['name', '=', 'partner.detail'],
    ]);
    $row = $view->read()[0];
    $arch = json_decode((string) $row['arch'], true, flags: JSON_THROW_ON_ERROR);
    $arch['header_actions'] = [
        'invalid',
        ['label' => '', 'url' => '/ignored'],
        ['label' => 'Works', 'url' => '/ok'],
    ];
    $view->write(['arch' => json_encode($arch, JSON_THROW_ON_ERROR)]);

    $locator = new ViewActionLocator;

    expect($locator->find($env, 'partners', 'partner.detail', 'header', 'works')['url'])->toBe('/ok')
        ->and($locator->find($env, 'partners', 'partner.detail', 'header', 'ignored'))->toBeNull();
});
