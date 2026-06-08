<?php

declare(strict_types=1);

use Velm\Admin\Arch\ArchTableConfigurator;
use Velm\Admin\Pages\PartnerListPage;
use Velm\Admin\Tests\TestCase;
use Velm\Views\ViewRegistry;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('partner list page resolves stored partner list arch', function (): void {
    $env = app(\Velm\Environment::class);
    $expected = (new ViewRegistry)->arch($env, 'partners', 'partner.list');

    $method = new ReflectionMethod(PartnerListPage::class, 'arch');
    $method->setAccessible(true);

    expect($method->invoke(app(PartnerListPage::class)))->toBe($expected);
});

test('partner list click-to-open resolves detail record url', function (): void {
    $page = app(PartnerListPage::class);
    $open = (new ReflectionMethod(PartnerListPage::class, 'openRecordUrl'))->invoke($page, 42);

    expect($open)->toBeString()
        ->toContain('/velm/views/partners/partner.detail/42');
});

test('partner list arch loads rows through the same table pipeline as ArchListPage', function (): void {
    $env = app(\Velm\Environment::class);
    $env->model('res.partner')->create(['name' => 'Velm SA List Test', 'active' => true]);

    $arch = (new ViewRegistry)->arch($env, 'partners', 'partner.list');
    $records = (new ArchTableConfigurator)->fetchRecords($arch, $env);
    $record = $records->firstWhere('name', 'Velm SA List Test');

    expect($record)->not->toBeNull()
        ->and($record['name'])->toBe('Velm SA List Test');
});

test('partner list arch declares demo page actions', function (): void {
    $env = app(\Velm\Environment::class);
    $arch = (new ViewRegistry)->arch($env, 'partners', 'partner.list');

    expect($arch['page_actions'] ?? [])->toHaveCount(3)
        ->and($arch['page_actions'][0]['label'])->toBe('Quick add')
        ->and($arch['page_actions'][0]['form']['sections'] ?? null)->not->toBeNull()
        ->and($arch['page_actions'][1]['label'])->toBe('Load demo data')
        ->and($arch['page_actions'][2]['label'])->toBe('Export CSV');
});

test('partner list page exposes analytics view switcher items', function (): void {
    $switcher = app(PartnerListPage::class)->analyticsViewSwitcher();

    expect($switcher)->not->toBeEmpty();

    $list = collect($switcher)->firstWhere('type', 'list');
    $kanban = collect($switcher)->firstWhere('type', 'kanban');

    expect($list)->not->toBeNull()
        ->and($list['active'])->toBeTrue()
        ->and($list['url'])->toBe(PartnerListPage::getUrl())
        ->and($kanban)->not->toBeNull()
        ->and($kanban['active'])->toBeFalse()
        ->and($kanban['url'])->toContain('/velm/views/partners/partner.kanban');
});

test('partner list page resolves quick add as inline form action', function (): void {
    $page = app(PartnerListPage::class);
    $quickAdd = $page->velmPageActions()[0];

    expect($quickAdd['label'])->toBe('Quick add')
        ->and($quickAdd['kind'])->toBe('inline_form')
        ->and($quickAdd['form_url'])->toBe('/web/view-actions/partners/partner.list/page/quick-add/form');
});

test('partner detail arch declares demo header actions', function (): void {
    $env = app(\Velm\Environment::class);
    $arch = (new ViewRegistry)->arch($env, 'partners', 'partner.detail');

    expect($arch['header_actions'] ?? [])->toHaveCount(3)
        ->and($arch['header_actions'][0]['label'])->toBe('Quick edit')
        ->and($arch['header_actions'][0]['form']['sections'] ?? null)->not->toBeNull()
        ->and($arch['header_actions'][1]['label'])->toBe('Duplicate')
        ->and($arch['header_actions'][2]['label'])->toBe('Export JSON');
});
