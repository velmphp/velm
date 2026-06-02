<?php

declare(strict_types=1);

use Velm\Views\Menu\MenuLayout;
use Velm\Views\Menu\MenuLayoutContext;
use Velm\Views\Menu\MenuTreeBuilder;

test('menu layout defaults to apps', function (): void {
    expect(MenuLayout::resolve(null))->toBe(MenuLayout::APPS);
});

test('menu layout reads sidebar from env', function (): void {
    putenv('VELM_MENU_LAYOUT=sidebar');

    try {
        expect(MenuLayout::resolve(null))->toBe(MenuLayout::SIDEBAR);
    } finally {
        putenv('VELM_MENU_LAYOUT');
    }
});

test('menu layout odoo alias maps to apps', function (): void {
    expect(MenuLayout::normalize('odoo'))->toBe(MenuLayout::APPS);
});

test('layout context apps secondary for active path', function (): void {
    $tree = [
        [
            'label' => 'Settings',
            'href' => null,
            'children' => [
                [
                    'label' => 'Users',
                    'href' => '/velm/views/admin/user.list',
                    'children' => [],
                ],
            ],
        ],
    ];

    $ctx = MenuLayoutContext::forTree(
        $tree,
        '/velm/views/admin/user.list',
        MenuLayout::APPS,
    );

    expect($ctx['menu_layout'])->toBe(MenuLayout::APPS)
        ->and($ctx['menu_active_root']['label'])->toBe('Settings')
        ->and($ctx['menu_active_root_index'])->toBe(0)
        ->and($ctx['menu_secondary'])->toHaveCount(1)
        ->and($ctx['menu_roots'][0]['nav_href'])->toBe('/velm/views/admin/user.list');
});

test('active root picks matching app by path', function (): void {
    $tree = [
        ['label' => 'A', 'href' => null, 'children' => []],
        [
            'label' => 'B',
            'href' => null,
            'children' => [
                ['label' => 'X', 'href' => '/velm/b', 'children' => []],
            ],
        ],
    ];

    [$root, $index] = MenuTreeBuilder::activeRoot($tree, '/velm/b');

    expect($root['label'])->toBe('B')
        ->and($index)->toBe(1);
});

test('entry href uses first descendant', function (): void {
    $node = [
        'label' => 'G',
        'href' => null,
        'children' => [
            ['label' => 'L', 'href' => '/leaf', 'children' => []],
        ],
    ];

    expect(MenuTreeBuilder::entryHref($node))->toBe('/leaf');
});

test('normalize path strips query and trailing slash', function (): void {
    expect(MenuTreeBuilder::normalizePath('/velm/views/a/b?x=1'))
        ->toBe('/velm/views/a/b');
});
