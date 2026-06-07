<?php

declare(strict_types=1);

use Velm\Database\PdoConnection;
use Velm\Environment;
use Velm\Registry;
use Velm\Schema\SchemaBuilder;
use Velm\Modules\Workflow\WorkflowDefinitionError;
use Velm\Modules\Workflow\WorkflowEngine;
use Velm\Ui\Forms\FormMode;
use Velm\Ui\Forms\FormRenderer;
use Velm\Ui\Support\RelationalInitials;
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

test('form renderer renders notebook sections with pages', function (): void {
    $env = app(Environment::class);

    $arch = [
        'view_type' => 'form',
        'model' => 'res.partner',
        'sections' => [[
            'name' => 'notebook',
            'title' => 'Tabs',
            'pages' => [
                ['name' => 'general', 'title' => 'General', 'fields' => [['name' => 'name']]],
                ['name' => 'flags', 'title' => 'Flags', 'fields' => [['name' => 'active']]],
            ],
        ]],
    ];

    $sections = (new FormRenderer)->sections($arch, $env, FormMode::Edit, ['name' => 'NB Co', 'active' => true]);

    expect($sections[0]->pages)->toHaveCount(2)
        ->and($sections[0]->pages[0]->title)->toBe('General')
        ->and($sections[0]->pages[1]->cells[0]->name)->toBe('active');
});

test('form renderer renders boolean and many2many widgets', function (): void {
    $env = app(Environment::class);
    $groupId = $env->model('res.groups')->search(limit: 1)->ids()[0];

    $arch = [
        'view_type' => 'form',
        'model' => 'res.users',
        'sections' => [[
            'name' => 'access',
            'fields' => [
                ['name' => 'active'],
                ['name' => 'group_ids', 'widget' => 'dialog'],
            ],
        ]],
    ];

    $sections = (new FormRenderer)->sections($arch, $env, FormMode::Edit, [
        'active' => true,
        'group_ids' => [$groupId],
    ]);

    expect($sections[0]->cells[0]->widgetProps['value'])->toBeTrue()
        ->and($sections[0]->cells[1]->widgetProps['dialogOnly'])->toBeTrue()
        ->and($sections[0]->cells[1]->widgetProps['initial'])->not->toBeEmpty();
});

test('relational initials attachment chips resolve from environment', function (): void {
    $env = app(Environment::class);

    $attId = $env->model('ir.attachment')->create([
        'name' => 'doc.txt',
        'mimetype' => 'text/plain',
        'type' => 'binary',
        'datas' => base64_encode('hello'),
        'file_size' => 5,
        'public' => false,
    ])->ids()[0];

    $chips = RelationalInitials::attachmentChips($env, $attId, false);

    expect($chips)->toHaveCount(1)
        ->and($chips[0]['name'])->toBe('doc.txt');
});

test('form renderer renders integer and text fields with layout hints', function (): void {
    $env = app(Environment::class);

    $arch = [
        'view_type' => 'form',
        'model' => 'res.partner',
        'cols' => 2,
        'sections' => [[
            'name' => 'details',
            'fields' => [
                ['name' => 'name', 'wide' => true, 'placeholder' => 'Legal name'],
            ],
        ]],
    ];

    $sections = (new FormRenderer)->sections($arch, $env, FormMode::Edit, ['name' => 'Wide Co']);

    expect($sections[0]->cells[0]->wide)->toBeTrue()
        ->and($sections[0]->cells[0]->widgetProps['placeholder'])->toBe('Legal name');
});

test('form renderer renders inline one2many with custom columns', function (): void {
    $env = Registry::using(function (Registry $registry): Environment {
        $registry->register(\Velm\Core\Tests\Support\OrderLine::class);
        $registry->register(\Velm\Core\Tests\Support\Order::class);
        $connection = \Velm\Database\PdoConnection::sqliteMemory();
        (new \Velm\Schema\SchemaBuilder($connection))->syncRegistry($registry);

        return new Environment($connection, $registry);
    });

    $orderId = $env->model('test.order')->create(['name' => 'SO-1'])->ids()[0];
    $lineId = $env->model('test.order.line')->create([
        'description' => 'Line item',
        'order_id' => $orderId,
    ])->ids()[0];

    $arch = [
        'view_type' => 'form',
        'model' => 'test.order',
        'sections' => [[
            'name' => 'lines',
            'fields' => [[
                'name' => 'line_ids',
                'widget' => 'inline',
                'columns' => [
                    ['name' => 'description'],
                ],
            ]],
        ]],
    ];

    $sections = (new FormRenderer)->sections(
        $arch,
        $env,
        FormMode::Edit,
        ['name' => 'SO-1', 'line_ids' => [$lineId]],
        [],
        $orderId,
    );

    expect($sections[0]->cells[0]->widgetProps['inline'])->toBeTrue()
        ->and($sections[0]->cells[0]->widgetProps['rows'])->toHaveCount(1)
        ->and($sections[0]->cells[0]->widgetProps['columns'])->toHaveCount(1);
});

test('form renderer renders boolean active field on partner form', function (): void {
    $env = app(Environment::class);

    $arch = [
        'view_type' => 'form',
        'model' => 'res.partner',
        'sections' => [[
            'name' => 'flags',
            'fields' => [
                ['name' => 'active'],
                ['name' => 'is_company', 'when_empty_use' => 'active'],
            ],
        ]],
    ];

    $sections = (new FormRenderer)->sections($arch, $env, FormMode::Edit, [
        'active' => false,
    ]);

    expect($sections[0]->cells[0]->widgetProps['value'])->toBeFalse()
        ->and($sections[0]->cells[1]->widgetProps['fallbackWireKey'])->toBe('data.active');
});
