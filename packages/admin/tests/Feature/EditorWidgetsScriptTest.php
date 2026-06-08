<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Velm\Admin\Pages\StoredViewEditPage;
use Velm\Admin\Pages\StoredViewRecordPage;
use Velm\Admin\Tests\TestCase;
use Velm\Environment;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('livewire shell pages include editor widget scripts in the layout', function (): void {
    $env = app(Environment::class);
    $id = (int) $env->model('res.partner')->create(['name' => 'Script Test', 'active' => true])->ids()[0];

    $this->actingAs(new GenericUser(['id' => 1, 'name' => 'Admin', 'email' => 'admin@test']))
        ->get(StoredViewEditPage::getUrl([
            'module' => 'partners',
            'viewName' => 'partner.form',
            'record' => $id,
        ]))
        ->assertOk()
        ->assertSee('pv-code-editor.js', false)
        ->assertSee('pv-rich-text.js', false)
        ->assertSee('pv-code-display.js', false);
});

test('server action detail page includes prism code display script', function (): void {
    $env = app(Environment::class);
    $actionId = (int) $env->model('ir.actions.server')->create([
        'name' => 'Script payload',
        'model' => 'res.partner',
        'action_type' => 'write',
        'vals_json' => '{"active": false}',
    ])->ids()[0];

    $this->actingAs(new GenericUser(['id' => 1, 'name' => 'Admin', 'email' => 'admin@test']))
        ->get(StoredViewRecordPage::getUrl([
            'module' => 'base',
            'viewName' => 'server.action.detail',
            'record' => $actionId,
        ]))
        ->assertOk()
        ->assertSee('pv-code-display.js', false)
        ->assertSee('data-pv-code-display', false);
});
