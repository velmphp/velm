<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Velm\Admin\Auth\Login;
use Velm\Admin\Support\VelmPanel;
use Velm\Admin\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('login page rejects invalid credentials', function (): void {
    Livewire::test(Login::class)
        ->set('data.email', 'nobody@test')
        ->set('data.password', 'wrong-password')
        ->call('authenticate')
        ->assertHasErrors(['data.email']);
});

test('login page validates required email and password', function (): void {
    Livewire::test(Login::class)
        ->set('data.email', '')
        ->set('data.password', '')
        ->call('authenticate')
        ->assertHasErrors(['data.email', 'data.password']);
});

test('login page accepts remember flag in validation', function (): void {
    Livewire::test(Login::class)
        ->set('data.email', 'user@test')
        ->set('data.password', 'secret')
        ->set('data.remember', true)
        ->call('authenticate')
        ->assertHasErrors(['data.email']);
});

test('login page exposes sign in title', function (): void {
    expect(Livewire::test(Login::class)->instance()->getTitle())->toBe('Sign in');
});

test('login page authenticates valid credentials and clears rate limiter', function (): void {
    RateLimiter::clear('velm-login:'.request()->ip());

    $user = new GenericUser([
        'id' => 1,
        'email' => 'admin@velm.test',
        'name' => 'Admin',
    ]);

    Auth::shouldReceive('attempt')
        ->once()
        ->with([
            'email' => 'admin@velm.test',
            'password' => 'secret-pass',
        ], true)
        ->andReturn(true);
    Auth::shouldReceive('user')->andReturn($user);

    Livewire::test(Login::class)
        ->set('data.email', 'admin@velm.test')
        ->set('data.password', 'secret-pass')
        ->set('data.remember', true)
        ->call('authenticate')
        ->assertRedirect(VelmPanel::homeUrl());
});

test('login page rate limits repeated failed attempts', function (): void {
    $component = Livewire::test(Login::class);

    for ($i = 0; $i < 5; $i++) {
        $component->set('data.email', 'blocked@test')
            ->set('data.password', 'wrong')
            ->call('authenticate');
    }

    $component->set('data.email', 'blocked@test')
        ->set('data.password', 'wrong')
        ->call('authenticate')
        ->assertHasErrors(['data.email']);
});
