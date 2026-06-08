<?php

declare(strict_types=1);

use Velm\Modules\ModuleInstaller;
use Velm\Modules\Tests\TestCase;
use Velm\Views\Menu\MenuLayoutContext;
use Velm\Views\Menu\MenuTreeBuilder;
use Velm\Views\Menu\ModuleLandingView;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }

    $roots = [dirname(__DIR__, 2).'/modules/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base']);
    $installer->install('partners', $roots);

    $this->env = $installer->environment($roots);
});

test('entry href prefers dashboard view when module has one', function (): void {
    $tree = (new MenuTreeBuilder)->build($this->env, null);
    $contacts = collect($tree)->firstWhere('label', 'Contacts');

    expect($contacts)->not->toBeNull()
        ->and(MenuTreeBuilder::entryHref($contacts, $this->env))
        ->toBe('/velm/views/partners/partner.dashboard');
});

test('menu layout context nav href opens dashboard for partners app', function (): void {
    $tree = (new MenuTreeBuilder)->build($this->env, null);

    $ctx = MenuLayoutContext::forTree(
        $tree,
        null,
        'apps',
        $this->env,
    );

    $contactsRoot = collect($ctx['menu_roots'])->firstWhere('label', 'Contacts');

    expect($contactsRoot)->not->toBeNull()
        ->and($contactsRoot['nav_href'])->toBe('/velm/views/partners/partner.dashboard');
});

test('entry href falls back to first descendant without dashboard', function (): void {
    $node = [
        'label' => 'G',
        'href' => null,
        'children' => [
            ['label' => 'L', 'href' => '/leaf', 'children' => []],
        ],
    ];

    expect(MenuTreeBuilder::entryHref($node))->toBe('/leaf');
});

test('module landing view parses stored view href', function (): void {
    expect(ModuleLandingView::moduleFromStoredViewHref('/velm/views/partners/partner.list'))
        ->toBe('partners')
        ->and(ModuleLandingView::storedViewHref('partners', 'partner.dashboard'))
        ->toBe('/velm/views/partners/partner.dashboard');
});
