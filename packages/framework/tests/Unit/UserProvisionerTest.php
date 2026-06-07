<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Velm\Framework\Auth\UserProvisioner;
use Velm\Framework\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }

    config([
        'velm.addon_paths' => [dirname(__DIR__, 3).'/modules/modules'],
        'velm.bootstrap_modules' => ['base'],
    ]);

    app(\Velm\Framework\VelmManager::class)->installBootstrap();
});

test('user provisioner syncs name and email onto res.users', function (): void {
    $env = app(\Velm\Environment::class);
    $userId = $env->model('res.users')->create([
        'name' => 'Old Name',
        'email' => 'sync@velm.test',
        'password' => 'secret',
    ])->ids()[0];

    $user = new class([
        'id' => $userId,
        'name' => 'Synced Admin',
        'email' => 'sync@velm.test',
        'remember_token' => null,
    ]) extends GenericUser {
        public function getAttribute(string $key): mixed
        {
            return $this->attributes[$key] ?? null;
        }
    };

    UserProvisioner::ensureProfile($user);

    $row = $env->browse('res.users', [$userId])->read()[0];

    expect($row['name'])->toBe('Synced Admin')
        ->and($row['email'])->toBe('sync@velm.test');
});

test('user provisioner bootstrap admin attaches admin group and company', function (): void {
    $env = app(\Velm\Environment::class);
    $email = 'bootstrap-admin@velm.test';

    $env->model('res.users')->create([
        'name' => 'Bootstrap Admin',
        'email' => $email,
        'password' => 'secret',
    ]);

    UserProvisioner::bootstrapAdminProfile($env, $email);

    $user = $env->model('res.users')->search([['email', '=', $email]], limit: 1)->read()[0];

    expect($user['active'])->toBeTrue()
        ->and($user['group_ids'])->not->toBeEmpty()
        ->and($user['company_id'])->not->toBeNull();
});

test('user provisioner no-ops for invalid auth id', function (): void {
    $user = new GenericUser([
        'id' => 0,
        'name' => 'Nobody',
        'email' => 'nobody@test',
        'remember_token' => null,
    ]);

    UserProvisioner::ensureProfile($user);

    expect(true)->toBeTrue();
});
