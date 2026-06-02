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

test('unknown op throws', function () {
    ArchOperations::apply(sampleArch(), [
        ['op' => 'frobnicate', 'target' => ['sections']],
    ]);
})->throws(InvalidArgumentException::class);
