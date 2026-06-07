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

test('graph data api returns labels and values for partner view', function (): void {
    $env = app(\Velm\Environment::class);
    $country = $env->model('res.country')->create(['name' => 'Belgium', 'code' => 'BE']);
    $env->model('res.partner')->create(['name' => 'API Graph Partner', 'country_id' => $country->ids()[0]]);

    $response = $this->getJson('/api/graph/data?'.http_build_query([
        'model' => 'res.partner',
        'module' => 'partners',
        'view' => 'partner.graph',
        'groupby' => 'country_id',
        'measure' => '__count',
    ]));

    $response->assertOk()
        ->assertJsonStructure(['labels', 'values', 'measure_label', 'groupby', 'measure']);

    expect($response->json('labels'))->toContain('Belgium')
        ->and(array_sum($response->json('values')))->toBeGreaterThan(0);
});

test('pivot data api returns pyvelm shaped payload', function (): void {
    $env = app(\Velm\Environment::class);
    $env->model('res.partner')->create(['name' => 'API Pivot Partner', 'is_company' => true, 'active' => true]);

    $response = $this->getJson('/api/pivot/data?'.http_build_query([
        'model' => 'res.partner',
        'module' => 'partners',
        'view' => 'partner.pivot',
        'row_groupby' => 'is_company',
        'col_groupby' => 'active',
        'measures' => '__count',
    ]));

    $response->assertOk()
        ->assertJsonStructure([
            'header_levels',
            'measure_label_row',
            'grand_header',
            'body_rows',
            'col_totals',
            'row_axis_titles',
            'measure_count',
        ]);
});

test('view fields api lists groupable and measurable fields', function (): void {
    $response = $this->getJson('/api/view-fields?model=res.partner');

    $response->assertOk()
        ->assertJsonStructure(['groupable', 'measurable']);

    expect(collect($response->json('groupable'))->pluck('value'))->toContain('country_id');
});
