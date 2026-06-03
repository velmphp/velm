<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Guard;
use Velm\Admin\Support\VelmPanel;
use Velm\Admin\Tests\TestCase;

uses(TestCase::class);

test('auth returns the session guard', function (): void {
    $this->actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']));

    $guard = VelmPanel::auth();

    expect($guard)->toBeInstanceOf(Guard::class)
        ->and($guard->user()?->email)->toBe('admin@test');
});
