<?php

declare(strict_types=1);

use Velm\Views\Arch\ArchOperations;

test('replace swaps a list entry by name', function (): void {
    $arch = [
        'sections' => [
            ['name' => 'main', 'fields' => [['name' => 'a'], ['name' => 'b']]],
        ],
    ];

    ArchOperations::apply($arch, [
        ['op' => 'replace', 'target' => ['sections', 'main', 'fields', 'a'], 'value' => ['name' => 'z']],
    ]);

    expect(array_column($arch['sections'][0]['fields'], 'name'))->toBe(['z', 'b']);
});

test('before inserts ahead of named sibling', function (): void {
    $arch = [
        'sections' => [
            ['name' => 'main', 'fields' => [['name' => 'a'], ['name' => 'b']]],
        ],
    ];

    ArchOperations::apply($arch, [
        ['op' => 'before', 'target' => ['sections', 'main', 'fields', 'b'], 'value' => ['name' => 'z']],
    ]);

    expect(array_column($arch['sections'][0]['fields'], 'name'))->toBe(['a', 'z', 'b']);
});

test('set writes dict key on parent node', function (): void {
    $arch = ['sections' => [['name' => 'main', 'title' => 'Old']]];

    ArchOperations::apply($arch, [
        ['op' => 'set', 'target' => ['sections', 'main', 'title'], 'value' => 'New'],
    ]);

    expect($arch['sections'][0]['title'])->toBe('New');
});

test('wildcard predicate finds nested field', function (): void {
    $arch = [
        'sections' => [
            [
                'name' => 'main',
                'fields' => [
                    ['name' => 'a', 'widget' => 'char'],
                    ['name' => 'b', 'widget' => 'text'],
                ],
            ],
        ],
    ];

    ArchOperations::apply($arch, [
        ['op' => 'set', 'target' => ['**', ['widget' => 'text'], 'readonly'], 'value' => true],
    ]);

    expect($arch['sections'][0]['fields'][1]['readonly'])->toBeTrue()
        ->and($arch['sections'][0]['fields'][0]['readonly'] ?? null)->toBeNull();
});

test('stepInto resolves list entries by name and predicate', function (): void {
    $node = [
        ['name' => 'a', 'value' => 1],
        ['name' => 'b', 'value' => 2],
    ];

    expect(ArchOperations::stepInto($node, 'b')['value'])->toBe(2)
        ->and(ArchOperations::stepInto($node, ['name' => 'a'])['value'])->toBe(1);
});

test('resolvePosition returns index for named list entry', function (): void {
    $parent = [['name' => 'x'], ['name' => 'y']];

    expect(ArchOperations::resolvePosition($parent, 'y'))->toBe(1);
});

test('matchesPredicate compares dict keys', function (): void {
    expect(ArchOperations::matchesPredicate(['name' => 'a', 'kind' => 'char'], ['kind' => 'char']))->toBeTrue()
        ->and(ArchOperations::matchesPredicate(['name' => 'a'], ['name' => 'b']))->toBeFalse();
});

test('update rejects non-dict value', function (): void {
    $arch = ['sections' => [['name' => 'main']]];

    ArchOperations::apply($arch, [
        ['op' => 'update', 'target' => ['sections', 'main'], 'value' => 'bad'],
    ]);
})->throws(InvalidArgumentException::class);

test('remove drops dict key on associative parent', function (): void {
    $arch = ['meta' => ['title' => 'Old', 'keep' => true]];

    ArchOperations::apply($arch, [
        ['op' => 'remove', 'target' => ['meta', 'title']],
    ]);

    expect($arch['meta'])->toBe(['keep' => true]);
});

test('append on empty target throws', function (): void {
    $arch = ['sections' => []];

    ArchOperations::apply($arch, [
        ['op' => 'append', 'target' => [], 'value' => ['name' => 'x']],
    ]);
})->throws(InvalidArgumentException::class);

test('resolvePosition accepts numeric list index', function (): void {
    $parent = [['name' => 'a'], ['name' => 'b']];

    expect(ArchOperations::resolvePosition($parent, 1))->toBe(1);
});

test('after inserts following named sibling', function (): void {
    $arch = [
        'sections' => [
            ['name' => 'main', 'fields' => [['name' => 'a'], ['name' => 'b']]],
        ],
    ];

    ArchOperations::apply($arch, [
        ['op' => 'after', 'target' => ['sections', 'main', 'fields', 'a'], 'value' => ['name' => 'z']],
    ]);

    expect(array_column($arch['sections'][0]['fields'], 'name'))->toBe(['a', 'z', 'b']);
});

test('append adds section when target resolves to sections list', function (): void {
    $arch = ['sections' => [['name' => 'main', 'fields' => []]]];

    ArchOperations::apply($arch, [
        ['op' => 'append', 'target' => ['sections'], 'value' => ['name' => 'extra', 'fields' => []]],
    ]);

    expect($arch['sections'])->toHaveCount(2)
        ->and($arch['sections'][1]['name'])->toBe('extra');
});

test('apply rethrows missing targets when skip flag disabled', function (): void {
    $arch = ['sections' => []];

    ArchOperations::apply($arch, [
        ['op' => 'set', 'target' => ['sections', 'missing', 'title'], 'value' => 'X'],
    ], skipMissingTargets: false);
})->throws(RuntimeException::class);
