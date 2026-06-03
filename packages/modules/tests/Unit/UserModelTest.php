<?php

declare(strict_types=1);

use Velm\Modules\Base\Models\User;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

test('res.users maps to users table and hashes passwords on prepare', function (): void {
    expect(User::table())->toBe('users');

    $prepared = User::prepareValues(['password' => 'secret'], 'create');

    expect($prepared['password'])->toStartWith('$2y$')
        ->and(User::prepareValues(['password' => ''], 'write'))->not->toHaveKey('password');
});

test('users table external columns are ignored by schema diff', function (): void {
    expect(User::schemaExternalColumns())->toContain('remember_token', 'password')
        ->and(User::baseFields())->toHaveKeys(['created_at', 'updated_at', 'password']);
});
