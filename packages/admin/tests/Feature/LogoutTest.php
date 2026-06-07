<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Velm\Admin\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('logout endpoint clears session and redirects to login', function (): void {
    $this->actingAs(new GenericUser([
        'id' => 1,
        'email' => 'admin@test',
        'remember_token' => null,
    ]));

    $response = $this->post('/'.Velm\Admin\Support\VelmPanel::path().'/logout');

    $response->assertRedirect(route('velm.auth.login'));
    expect(auth()->check())->toBeFalse();
});
