<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Velm\Admin\Pages\StoredViewEditPage;
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
        ->assertSee('pv-rich-text.js', false);
});
