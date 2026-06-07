<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Velm\Admin\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }

    $this->actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']));
});

test('graph data api rejects unknown model', function (): void {
    $this->getJson('/api/graph/data?model=not.a.model&groupby=name')
        ->assertStatus(400)
        ->assertJsonPath('message', 'Unknown model not.a.model.');
});

test('pivot data api rejects unknown model', function (): void {
    $this->getJson('/api/pivot/data?model=missing.model&row_groupby=name')
        ->assertStatus(400)
        ->assertJsonPath('message', 'Unknown model missing.model.');
});

test('graph data api works without module view using model fallback arch', function (): void {
    $env = app(\Velm\Environment::class);
    $env->model('res.partner')->create(['name' => 'Fallback Graph', 'active' => true]);
    $env->model('res.partner')->create(['name' => 'Fallback Graph 2', 'active' => false]);

    $response = $this->getJson('/api/graph/data?'.http_build_query([
        'model' => 'res.partner',
        'groupby' => 'active',
        'measure' => '__count',
        'search' => 'Fallback',
    ]));

    $response->assertOk()
        ->assertJsonStructure(['labels', 'values', 'measure_label', 'groupby', 'measure']);
});

test('graph data api returns 400 for unknown groupby field', function (): void {
    $this->getJson('/api/graph/data?'.http_build_query([
        'model' => 'res.partner',
        'groupby' => 'not_a_real_field',
        'measure' => '__count',
    ]))->assertStatus(400);
});

test('pivot data api returns 400 for unknown row groupby field', function (): void {
    $this->getJson('/api/pivot/data?'.http_build_query([
        'model' => 'res.partner',
        'row_groupby' => 'missing_field',
        'measures' => '__count',
    ]))->assertStatus(400);
});

test('graph and pivot data apis return 403 when model access is denied', function (): void {
    $this->actingAs(new GenericUser(['id' => 999, 'email' => 'noaccess@test']));

    $this->getJson('/api/graph/data?model=res.partner&groupby=active')
        ->assertStatus(403);

    $this->getJson('/api/pivot/data?model=res.partner&row_groupby=active')
        ->assertStatus(403);
});

test('pivot data api accepts multi measure specs', function (): void {
    $env = app(\Velm\Environment::class);
    $env->model('res.partner')->create(['name' => 'Multi Pivot', 'is_company' => true, 'active' => true]);

    $this->getJson('/api/pivot/data?'.http_build_query([
        'model' => 'res.partner',
        'row_groupby' => 'is_company',
        'col_groupby' => 'active',
        'measures' => '__count',
        'search' => 'Multi',
    ]))->assertOk()->assertJsonStructure(['body_rows', 'header_levels']);
});
