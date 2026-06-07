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

test('wildcard update requires selector after star star', function (): void {
    $arch = [
        'sections' => [
            ['name' => 'main', 'fields' => [['name' => 'a']]],
        ],
    ];

    ArchOperations::apply($arch, [
        ['op' => 'update', 'target' => ['**'], 'value' => ['readonly' => true]],
    ], skipMissingTargets: false);
})->throws(InvalidArgumentException::class, "'**' must be followed");

test('prepend on non-list target throws', function (): void {
    $arch = ['sections' => [['name' => 'main', 'title' => 'Main']]];

    ArchOperations::apply($arch, [
        ['op' => 'prepend', 'target' => ['sections', 'main'], 'value' => ['name' => 'x']],
    ], skipMissingTargets: false);
})->throws(InvalidArgumentException::class, 'requires a list target');

test('before on associative parent throws', function (): void {
    $arch = ['meta' => ['title' => 'Old']];

    ArchOperations::apply($arch, [
        ['op' => 'before', 'target' => ['meta', 'title'], 'value' => 'New'],
    ], skipMissingTargets: false);
})->throws(InvalidArgumentException::class, 'requires a list parent');

test('stepInto resolves integer index and dict keys', function (): void {
    $node = [
        'sections' => [
            ['name' => 'main'],
        ],
    ];

    expect(ArchOperations::stepInto($node, 'sections')[0]['name'])->toBe('main')
        ->and(ArchOperations::stepInto($node['sections'], 0)['name'])->toBe('main');
});

test('resolvePosition throws for invalid list index', function (): void {
    $parent = [['name' => 'a']];

    ArchOperations::resolvePosition($parent, 3);
})->throws(RuntimeException::class, 'out of range');

test('matchesPredicate returns false for non-array items', function (): void {
    expect(ArchOperations::matchesPredicate('text', ['name' => 'a']))->toBeFalse();
});

test('remove splices list entries by numeric index', function (): void {
    $arch = [
        'sections' => [
            ['name' => 'main', 'fields' => [['name' => 'a'], ['name' => 'b']]],
        ],
    ];

    ArchOperations::apply($arch, [
        ['op' => 'remove', 'target' => ['sections', 'main', 'fields', 0]],
    ]);

    expect(array_column($arch['sections'][0]['fields'], 'name'))->toBe(['b']);
});

test('set replaces list entry by numeric index', function (): void {
    $arch = [
        'sections' => [
            ['name' => 'main', 'fields' => [['name' => 'a'], ['name' => 'b']]],
        ],
    ];

    ArchOperations::apply($arch, [
        ['op' => 'set', 'target' => ['sections', 'main', 'fields', 1], 'value' => ['name' => 'z']],
    ]);

    expect(array_column($arch['sections'][0]['fields'], 'name'))->toBe(['a', 'z']);
});

test('wildcard lookup failure wraps runtime exception', function (): void {
    $arch = ['sections' => [['name' => 'main', 'fields' => []]]];

    ArchOperations::apply($arch, [
        ['op' => 'set', 'target' => ['**', 'missing', 'readonly'], 'value' => true],
    ], skipMissingTargets: false);
})->throws(RuntimeException::class, '`**` lookup found no descendant');

test('empty target on replace throws', function (): void {
    $arch = ['sections' => []];

    ArchOperations::apply($arch, [
        ['op' => 'replace', 'target' => [], 'value' => ['name' => 'x']],
    ], skipMissingTargets: false);
})->throws(InvalidArgumentException::class, 'empty target');

test('stepInto rejects invalid segment types', function (): void {
    $node = [['name' => 'a']];

    ArchOperations::stepInto($node, 1.5);
})->throws(InvalidArgumentException::class, "can't step into list");

test('resolvePosition matches predicate segments', function (): void {
    $parent = [
        ['name' => 'a', 'widget' => 'char'],
        ['name' => 'b', 'widget' => 'text'],
    ];

    expect(ArchOperations::resolvePosition($parent, ['widget' => 'text']))->toBe(1);
});

test('resolvePosition throws for missing dict key', function (): void {
    $parent = ['meta' => ['title' => 'Old']];

    ArchOperations::resolvePosition($parent, 'missing');
})->throws(RuntimeException::class, 'no key missing');

test('resolvePosition throws for invalid associative segment type', function (): void {
    ArchOperations::resolvePosition(['meta' => ['title' => 'Old']], 1);
})->throws(InvalidArgumentException::class, "can't address array");
