<?php

declare(strict_types=1);

use Velm\Views\Arch\ArchOperations;

/**
 * @return array<string, mixed>
 */
function sampleArch(): array
{
    return [
        'sections' => [
            [
                'name' => 'main',
                'fields' => [
                    ['name' => 'a'],
                    ['name' => 'b'],
                ],
            ],
        ],
    ];
}

test('update merges into dict at target', function () {
    $arch = sampleArch();

    ArchOperations::apply($arch, [
        ['op' => 'update', 'target' => ['sections', 'main'], 'value' => ['label' => 'Main']],
    ]);

    expect($arch['sections'][0]['label'])->toBe('Main');
});

test('remove drops a list field entry', function () {
    $arch = sampleArch();

    ArchOperations::apply($arch, [
        ['op' => 'remove', 'target' => ['sections', 'main', 'fields', 'a']],
    ]);

    expect(array_column($arch['sections'][0]['fields'], 'name'))->toBe(['b']);
});

test('after inserts before the next sibling', function () {
    $arch = sampleArch();

    ArchOperations::apply($arch, [
        ['op' => 'after', 'target' => ['sections', 'main', 'fields', 'a'], 'value' => ['name' => 'z']],
    ]);

    expect(array_column($arch['sections'][0]['fields'], 'name'))->toBe(['a', 'z', 'b']);
});

test('wildcard sets attribute on a nested field', function () {
    $arch = sampleArch();

    ArchOperations::apply($arch, [
        ['op' => 'set', 'target' => ['**', 'a', 'readonly'], 'value' => true],
    ]);

    expect($arch['sections'][0]['fields'][0]['readonly'])->toBeTrue();
});

test('append and prepend mutate section field lists', function () {
    $arch = sampleArch();

    ArchOperations::apply($arch, [
        ['op' => 'prepend', 'target' => ['sections', 'main', 'fields'], 'value' => ['name' => 'z']],
        ['op' => 'append', 'target' => ['sections', 'main', 'fields'], 'value' => ['name' => 'c']],
    ]);

    expect(array_column($arch['sections'][0]['fields'], 'name'))->toBe(['z', 'a', 'b', 'c']);
});

test('skips missing targets when third-party patches conflict', function () {
    $arch = [
        'sections' => [
            ['name' => 'organization', 'title' => 'Organization', 'fields' => [['name' => 'company_id']]],
            ['name' => 'identity', 'title' => 'Identity', 'fields' => [['name' => 'name']]],
        ],
    ];

    ArchOperations::apply($arch, [
        ['op' => 'remove', 'target' => ['sections', 'organization']],
        ['op' => 'update', 'target' => ['sections', 'organization'], 'value' => ['title' => 'Org']],
    ]);

    expect(array_column($arch['sections'], 'name'))->toBe(['identity']);
});

test('strict mode throws when inherit target is missing', function () {
    $arch = sampleArch();

    ArchOperations::apply($arch, [
        ['op' => 'update', 'target' => ['sections', 'missing'], 'value' => ['title' => 'X']],
    ], skipMissingTargets: false);
})->throws(RuntimeException::class);

test('unknown op throws', function () {
    $arch = sampleArch();

    ArchOperations::apply($arch, [
        ['op' => 'frobnicate', 'target' => ['sections']],
    ]);
})->throws(InvalidArgumentException::class);
