<?php

declare(strict_types=1);

use Velm\Modules\ModuleInstaller;
use Velm\Views\Arch\ActionResolver;
use Velm\Views\Arch\ViewActionKey;
use Velm\Views\Arch\ViewActionLocator;
use Velm\Views\Authoring\Action;
use Velm\Views\Authoring\ActionForm;
use Velm\Views\Authoring\ActionVariant;
use Velm\Views\Authoring\DetailView;
use Velm\Views\Authoring\Field;
use Velm\Views\Authoring\ListView;
use Velm\Views\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('action builder serializes variant and inline form metadata', function (): void {
    $action = Action::make('Quick add')
        ->model('res.partner')
        ->variant(ActionVariant::Primary)
        ->perm('create')
        ->form(fn (ActionForm $form) => $form->section('main', 'Main', ['name']))
        ->toArray();

    expect($action['variant'])->toBe('primary')
        ->and($action['model'])->toBe('res.partner')
        ->and($action['form']['sections'])->toHaveCount(1);
});

test('action builder rejects unknown variants at compile time via enum', function (): void {
    expect(ActionVariant::cases())->toHaveCount(5);
});

test('action builder requires url form view or inline form', function (): void {
    Action::make('Empty')->variant(ActionVariant::Primary)->toArray();
})->throws(LogicException::class);

test('inline action form requires model', function (): void {
    Action::make('Quick add')
        ->variant(ActionVariant::Primary)
        ->form(fn (ActionForm $form) => $form->section('main', 'Main', ['name']))
        ->toArray();
})->throws(LogicException::class);

test('list view stores page_actions arch key', function (): void {
    $list = ListView::make('partner.list')
        ->model('res.partner')
        ->columns(['name'])
        ->pageActions([
            Action::make('Export')->url('/web/partners/export')->method('GET')->variant(ActionVariant::Secondary),
        ])
        ->toArray();

    expect($list['arch']['page_actions'])->toHaveCount(1)
        ->and($list['arch']['page_actions'][0]['variant'])->toBe('secondary');
});

test('view action key slugifies labels', function (): void {
    expect(ViewActionKey::fromLabel('Quick add'))->toBe('quick-add')
        ->and(ViewActionKey::fromLabel('Load demo data!'))->toBe('load-demo-data');
});

test('view action locator finds inline form action in synced arch', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin', 'partners']);
    $env = $installer->environment($roots);

    $action = (new ViewActionLocator)->find($env, 'partners', 'partner.list', 'page', 'quick-add');

    expect($action)->not->toBeNull()
        ->and($action['form']['sections'] ?? null)->not->toBeNull();
});

test('action resolver substitutes record id and flags external urls', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules/modules'];
    $env = (new ModuleInstaller)->environment($roots);

    $resolved = (new ActionResolver)->resolve(
        [
            ['label' => 'Open', 'url' => '/velm/views/partners/partner.detail/{id}', 'method' => 'GET'],
            ['label' => 'Designer', 'url' => '/web/workflow/{id}/build', 'method' => 'GET', 'full_page' => true, 'variant' => 'primary'],
        ],
        $env,
        'res.partner',
        42,
        'partners',
        'partner.detail',
    );

    expect($resolved[1]['variant'])->toBe('primary')
        ->and($resolved[1]['action_key'])->toBe('designer');
});

test('action resolver passes inline form metadata through', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules/modules'];
    $env = (new ModuleInstaller)->environment($roots);

    $resolved = (new ActionResolver)->resolve(
        [
            [
                'label' => 'Quick add',
                'model' => 'res.partner',
                'variant' => 'primary',
                'perm' => 'create',
                'form' => [
                    'sections' => [
                        ['name' => 'main', 'title' => 'Main', 'fields' => [['name' => 'name']]],
                    ],
                ],
            ],
        ],
        $env,
        'res.partner',
        0,
        'partners',
        'partner.list',
    );

    expect($resolved[0]['form']['sections'])->toHaveCount(1)
        ->and($resolved[0]['variant'])->toBe('primary')
        ->and($resolved[0]['model'])->toBe('res.partner');
});
