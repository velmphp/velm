<?php

declare(strict_types=1);

use Velm\Environment;
use Velm\Ui\Forms\FormMode;
use Velm\Ui\Forms\FormRenderer;
use Velm\Ui\Tests\TestCase;
use Velm\Views\Arch\ArchNormalizer;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('form renderer builds sections from normalized arch', function (): void {
    $env = app(Environment::class);

    $arch = ArchNormalizer::normalizeForm([
        'model' => 'res.partner',
        'sections' => [
            [
                'name' => 'main',
                'title' => 'Identity',
                'fields' => [
                    ['name' => 'name'],
                    ['name' => 'country_id'],
                ],
            ],
        ],
    ]);

    $sections = (new FormRenderer)->sections($arch, $env, FormMode::Edit, ['name' => 'Acme']);

    expect($sections)->toHaveCount(1)
        ->and($sections[0]->title)->toBe('Identity')
        ->and($sections[0]->cells)->toHaveCount(2)
        ->and($sections[0]->cells[0]->widget)->toBe('velm-ui::widgets.char-input')
        ->and($sections[0]->cells[1]->widget)->toBe('velm-ui::widgets.m2o-input');
});

test('form renderer applies view cols and field colspan', function (): void {
    $env = app(Environment::class);

    $arch = ArchNormalizer::normalizeForm([
        'model' => 'res.partner',
        'cols' => 3,
        'sections' => [[
            'name' => 'main',
            'title' => 'Main',
            'fields' => [
                ['name' => 'name'],
                ['name' => 'notes', 'wide' => true],
                ['name' => 'active', 'widget' => 'toggle', 'colspan' => 2],
            ],
        ]],
    ]);

    $sections = (new FormRenderer)->sections($arch, $env, FormMode::Edit, []);

    expect($sections[0]->cols)->toBe(3)
        ->and($sections[0]->cells[0]->colspan)->toBe(1)
        ->and($sections[0]->cells[0]->wide)->toBeFalse()
        ->and($sections[0]->cells[1]->wide)->toBeTrue()
        ->and($sections[0]->cells[2]->colspan)->toBe(2);
});

test('form renderer builds notebook sections', function (): void {
    $env = app(Environment::class);

    $arch = [
        'model' => 'res.partner',
        'sections' => [[
            'name' => 'tabs',
            'title' => 'Details',
            'pages' => [
                ['name' => 'a', 'title' => 'Tab A', 'fields' => ['name']],
                ['name' => 'b', 'title' => 'Tab B', 'fields' => [['name' => 'active', 'widget' => 'toggle']]],
            ],
        ]],
    ];

    $sections = (new FormRenderer)->sections($arch, $env, FormMode::Edit, ['name' => 'X', 'active' => true]);

    expect($sections)->toHaveCount(1)
        ->and($sections[0]->kind->value)->toBe('notebook')
        ->and($sections[0]->pages)->toHaveCount(2)
        ->and($sections[0]->storageKey)->toContain('pv-nb-');
});
