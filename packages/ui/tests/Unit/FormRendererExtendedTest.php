<?php

declare(strict_types=1);

use Velm\Environment;
use Velm\Modules\Workflow\WorkflowDefinitionError;
use Velm\Modules\Workflow\WorkflowEngine;
use Velm\Ui\Forms\FormMode;
use Velm\Ui\Forms\FormRenderer;
use Velm\Ui\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('form renderer display mode marks fields readonly', function (): void {
    $env = app(Environment::class);

    $arch = [
        'view_type' => 'form',
        'model' => 'res.partner',
        'sections' => [[
            'name' => 'main',
            'title' => 'Main',
            'fields' => [['name' => 'name']],
        ]],
    ];

    $sections = (new FormRenderer)->sections($arch, $env, FormMode::Display, ['name' => 'Readonly Co']);

    expect($sections[0]->cells[0]->widgetProps['readonly'])->toBeTrue()
        ->and($sections[0]->cells[0]->required)->toBeFalse();
});

test('form renderer surfaces field errors on cells', function (): void {
    $env = app(Environment::class);

    $arch = [
        'view_type' => 'form',
        'model' => 'res.partner',
        'sections' => [[
            'name' => 'main',
            'fields' => [['name' => 'name', 'placeholder' => 'Legal name']],
        ]],
    ];

    $sections = (new FormRenderer)->sections(
        $arch,
        $env,
        FormMode::Edit,
        [],
        ['name' => 'Name is required'],
    );

    expect($sections[0]->cells[0]->error)->toBe('Name is required')
        ->and($sections[0]->cells[0]->widgetProps['placeholder'])->toBe('Legal name');
});

test('form renderer resolves many2one initial label from related record', function (): void {
    $env = app(Environment::class);
    $country = $env->model('res.country')->create(['name' => 'Belgium', 'code' => 'BE']);
    $countryId = $country->ids()[0];

    $arch = [
        'view_type' => 'form',
        'model' => 'res.partner',
        'sections' => [[
            'name' => 'main',
            'fields' => [['name' => 'country_id']],
        ]],
    ];

    $sections = (new FormRenderer)->sections($arch, $env, FormMode::Edit, ['country_id' => $countryId]);

    expect($sections[0]->cells[0]->widgetProps['initialId'])->toBe($countryId)
        ->and($sections[0]->cells[0]->widgetProps['initialLabel'])->toBe('Belgium');
});

test('form renderer normalizes detail arch view type', function (): void {
    $env = app(Environment::class);

    $arch = [
        'view_type' => 'detail',
        'model' => 'res.partner',
        'sections' => [[
            'name' => 'identity',
            'title' => 'Identity',
            'fields' => [['name' => 'name']],
        ]],
    ];

    $sections = (new FormRenderer)->sections($arch, $env, FormMode::Display, ['name' => 'Detail Co']);

    expect($sections[0]->title)->toBe('Identity');
});
