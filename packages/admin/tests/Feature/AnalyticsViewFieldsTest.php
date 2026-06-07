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

test('view fields api rejects empty and unknown models', function (): void {
    $this->getJson('/api/view-fields')
        ->assertStatus(400)
        ->assertJsonPath('message', 'Unknown model .');

    $this->getJson('/api/view-fields?model=missing.model')
        ->assertStatus(400)
        ->assertJsonPath('message', 'Unknown model missing.model.');
});

test('view fields api returns catalog for partner model', function (): void {
    $response = $this->getJson('/api/view-fields?model=res.partner')->assertOk();

    expect($response->json('groupable'))->toBeArray()
        ->and($response->json('measurable'))->toBeArray();
});


