<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Velm\Framework\Auth\UserProvisioner;
use Velm\Framework\Tests\TestCase;

uses(TestCase::class);

test('user provisioner runs without error for authenticated user', function (): void {
    $user = new GenericUser([
        'id' => 1,
        'name' => 'Synced Admin',
        'email' => 'admin@velm.test',
        'remember_token' => null,
    ]);

    UserProvisioner::ensureProfile($user);

    expect(true)->toBeTrue();
});
