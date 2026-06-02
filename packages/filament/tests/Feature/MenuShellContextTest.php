<?php

declare(strict_types=1);

use Velm\Filament\Tests\TestCase;
use Velm\Views\Menu\MenuLayout;
use Velm\Views\Menu\MenuTreeBuilder;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('menu tree builder marks partner list active for canonical path', function (): void {
    $env = app(\Velm\Environment::class);
    $tree = app(MenuTreeBuilder::class)->build(
        $env,
        '/velm/views/partners/partner.list',
    );

    expect($tree)->not->toBeEmpty();

    $contacts = collect($tree)->firstWhere('label', 'Contacts');

    expect($contacts)->not->toBeNull()
        ->and($contacts['active'] ?? false)->toBeTrue();
});

test('menu layout context builds apps secondary for partners', function (): void {
    $env = app(\Velm\Environment::class);
    $tree = app(MenuTreeBuilder::class)->build(
        $env,
        '/velm/views/partners/partner.list',
    );

    $ctx = \Velm\Views\Menu\MenuLayoutContext::forTree(
        $tree,
        '/velm/views/partners/partner.list',
        MenuLayout::APPS,
    );

    expect($ctx['menu_secondary'])->toHaveCount(1)
        ->and($ctx['menu_secondary'][0]['label'])->toBe('Partners');
});

test('menu layout config defaults to apps', function (): void {
    expect(config('velm.menu_layout'))->toBe(MenuLayout::APPS);
});
