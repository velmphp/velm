<?php

declare(strict_types=1);

use Velm\Environment;
use Velm\Ui\Forms\InlineActionFormRenderer;
use Velm\Ui\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('inline action form renderer maps char boolean and many2one fields', function (): void {
    $env = app(Environment::class);
    $countryId = $env->model('res.country')->create(['name' => 'Belgium', 'code' => 'BE'])->ids()[0];

    $fields = (new InlineActionFormRenderer)->fields($env, 'res.partner', [
        'sections' => [
            [
                'name' => 'identity',
                'title' => 'Identity',
                'fields' => [
                    'name',
                    ['name' => 'active', 'widget' => 'toggle'],
                    'country_id',
                ],
            ],
        ],
    ], [
        'name' => 'Acme',
        'active' => true,
        'country_id' => $countryId,
    ]);

    $byName = collect($fields)->keyBy('name');

    expect($byName['name']['type'])->toBe('char')
        ->and($byName['name']['value'])->toBe('Acme')
        ->and($byName['active']['type'])->toBe('boolean')
        ->and($byName['active']['value'])->toBeTrue()
        ->and($byName['country_id']['type'])->toBe('many2one')
        ->and($byName['country_id']['value'])->toBe($countryId)
        ->and(collect($byName['country_id']['options'])->pluck('id'))->toContain($countryId);
});

test('inline action form renderer supports notebook pages and text widgets', function (): void {
    $env = app(Environment::class);

    $fields = (new InlineActionFormRenderer)->fields($env, 'res.partner', [
        'sections' => [
            [
                'name' => 'notebook',
                'title' => 'Notebook',
                'pages' => [
                    [
                        'name' => 'notes',
                        'title' => 'Notes',
                        'fields' => [
                            ['name' => 'name', 'widget' => 'text'],
                            ['name' => 'unknown_field', 'widget' => 'text'],
                        ],
                    ],
                ],
            ],
        ],
    ], []);

    expect($fields)->toHaveCount(2)
        ->and($fields[0]['type'])->toBe('text')
        ->and($fields[0]['multiline'] ?? false)->toBeTrue()
        ->and($fields[1]['type'])->toBe('text')
        ->and($fields[1]['name'])->toBe('unknown_field');
});

test('inline action form renderer skips invalid field specs and uses custom labels', function (): void {
    $env = app(Environment::class);

    $fields = (new InlineActionFormRenderer)->fields($env, 'res.partner', [
        'sections' => [
            [
                'name' => 'main',
                'title' => 'Main',
                'fields' => [
                    ['label' => 'Only label'],
                    ['name' => 'name', 'label' => 'Partner name'],
                ],
            ],
        ],
    ], []);

    expect($fields)->toHaveCount(1)
        ->and($fields[0]['label'])->toBe('Partner name')
        ->and($fields[0]['type'])->toBe('char');
});

test('inline action form renderer maps integer fields', function (): void {
    $env = app(Environment::class);

    $fields = (new InlineActionFormRenderer)->fields($env, 'res.currency', [
        'sections' => [
            [
                'name' => 'main',
                'title' => 'Main',
                'fields' => ['decimal_places'],
            ],
        ],
    ], ['decimal_places' => 2]);

    expect($fields[0]['type'])->toBe('integer')
        ->and($fields[0]['value'])->toBe(2);
});
