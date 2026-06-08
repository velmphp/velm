<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Velm\Admin\Pages\ChangePasswordPage;
use Velm\Admin\Pages\ProfilePage;
use Velm\Admin\Tests\TestCase;
use Velm\Environment;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('profile page renders for authenticated user', function (): void {
    $env = app(Environment::class);
    $userId = $env->model('res.users')->create([
        'name' => 'Taylor',
        'email' => 'taylor@velm.test',
        'password' => 'secret',
    ])->ids()[0];

    app()->instance(Environment::class, new Environment(
        $env->connection,
        $env->registry,
        $userId,
    ));

    Livewire::actingAs(new GenericUser(['id' => $userId, 'name' => 'Taylor', 'email' => 'taylor@velm.test']))
        ->test(ProfilePage::class)
        ->assertOk()
        ->assertSet('data.name', 'Taylor')
        ->assertSet('data.email', 'taylor@velm.test');
});

test('profile page saves name and email', function (): void {
    $env = app(Environment::class);
    $userId = $env->model('res.users')->create([
        'name' => 'Before',
        'email' => 'before@velm.test',
        'password' => 'secret',
    ])->ids()[0];

    app()->instance(Environment::class, new Environment(
        $env->connection,
        $env->registry,
        $userId,
    ));

    Livewire::actingAs(new GenericUser(['id' => $userId, 'email' => 'before@velm.test']))
        ->test(ProfilePage::class)
        ->set('data.name', 'After Name')
        ->set('data.email', 'after@velm.test')
        ->call('saveProfile')
        ->assertHasNoErrors();

    $row = $env->withAclBypass(fn () => $env->browse('res.users', [$userId])->read(['name', 'email'])[0]);

    expect($row['name'])->toBe('After Name')
        ->and($row['email'])->toBe('after@velm.test')
        ->and(DB::table('users')->where('id', $userId)->value('email'))->toBe('after@velm.test');
});

test('profile page rejects duplicate email', function (): void {
    $env = app(Environment::class);
    $env->model('res.users')->create([
        'name' => 'Other',
        'email' => 'taken@velm.test',
        'password' => 'secret',
    ]);
    $userId = $env->model('res.users')->create([
        'name' => 'Self',
        'email' => 'self@velm.test',
        'password' => 'secret',
    ])->ids()[0];

    app()->instance(Environment::class, new Environment(
        $env->connection,
        $env->registry,
        $userId,
    ));

    Livewire::actingAs(new GenericUser(['id' => $userId, 'email' => 'self@velm.test']))
        ->test(ProfilePage::class)
        ->set('data.email', 'taken@velm.test')
        ->call('saveProfile')
        ->assertHasErrors(['data.email']);
});

test('change password page updates hash when current password matches', function (): void {
    $env = app(Environment::class);
    $userId = $env->model('res.users')->create([
        'name' => 'Pwd User',
        'email' => 'pwd@velm.test',
        'password' => 'old-secret',
    ])->ids()[0];

    $oldHash = DB::table('users')->where('id', $userId)->value('password');

    app()->instance(Environment::class, new Environment(
        $env->connection,
        $env->registry,
        $userId,
    ));

    Livewire::actingAs(new GenericUser(['id' => $userId, 'email' => 'pwd@velm.test']))
        ->test(ChangePasswordPage::class)
        ->set('data.current_password', 'old-secret')
        ->set('data.password', 'new-secret-99')
        ->set('data.password_confirmation', 'new-secret-99')
        ->call('savePassword')
        ->assertHasNoErrors();

    $newHash = DB::table('users')->where('id', $userId)->value('password');

    expect($newHash)->not->toBe($oldHash)
        ->and(Hash::check('new-secret-99', (string) $newHash))->toBeTrue();
});

test('change password page rejects wrong current password', function (): void {
    $env = app(Environment::class);
    $userId = $env->model('res.users')->create([
        'name' => 'Pwd User',
        'email' => 'wrong-pwd@velm.test',
        'password' => 'old-secret',
    ])->ids()[0];

    app()->instance(Environment::class, new Environment(
        $env->connection,
        $env->registry,
        $userId,
    ));

    Livewire::actingAs(new GenericUser(['id' => $userId, 'email' => 'wrong-pwd@velm.test']))
        ->test(ChangePasswordPage::class)
        ->set('data.current_password', 'not-the-password')
        ->set('data.password', 'new-secret-99')
        ->set('data.password_confirmation', 'new-secret-99')
        ->call('savePassword')
        ->assertHasErrors(['data.current_password']);
});
