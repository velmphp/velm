<?php

declare(strict_types=1);

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Velm\Admin\Support\UserAccountUpdater;
use Velm\Admin\Support\VelmPanel;
use Velm\Admin\Tests\TestCase;
use Velm\Environment;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

function bindSelfUserAccountEnv(int $userId): Environment
{
    $env = app(Environment::class);
    $bound = new Environment($env->connection, $env->registry, $userId);
    app()->instance(Environment::class, $bound);

    return $bound;
}

test('user account updater rejects updates for another user', function (): void {
    $env = app(Environment::class);
    $userId = (int) $env->model('res.users')->create([
        'name' => 'Self',
        'email' => 'self@velm.test',
        'password' => 'secret',
    ])->ids()[0];

    VelmPanel::auth()->login(new GenericUser(['id' => 999, 'email' => 'other@test']));

    expect(fn () => (new UserAccountUpdater($env))->updateProfile($userId, ['name' => 'Hacker']))
        ->toThrow(AuthenticationException::class);
});

test('user account updater rejects blank name and email', function (): void {
    $env = app(Environment::class);
    $userId = (int) $env->model('res.users')->create([
        'name' => 'Valid',
        'email' => 'valid@velm.test',
        'password' => 'secret',
    ])->ids()[0];

    VelmPanel::auth()->login(new GenericUser(['id' => $userId, 'email' => 'valid@velm.test']));
    $updater = new UserAccountUpdater(bindSelfUserAccountEnv($userId));

    expect(fn () => $updater->updateProfile($userId, ['name' => '   ']))
        ->toThrow(InvalidArgumentException::class, 'Name is required')
        ->and(fn () => $updater->updateProfile($userId, ['email' => '']))
        ->toThrow(InvalidArgumentException::class, 'Email is required');
});

test('user account updater rejects duplicate email on res users', function (): void {
    $env = app(Environment::class);
    $env->model('res.users')->create([
        'name' => 'Taken',
        'email' => 'taken@velm.test',
        'password' => 'secret',
    ]);
    $userId = (int) $env->model('res.users')->create([
        'name' => 'Self',
        'email' => 'self@velm.test',
        'password' => 'secret',
    ])->ids()[0];

    VelmPanel::auth()->login(new GenericUser(['id' => $userId, 'email' => 'self@velm.test']));
    $updater = new UserAccountUpdater(bindSelfUserAccountEnv($userId));

    expect(fn () => $updater->updateProfile($userId, ['email' => 'taken@velm.test']))
        ->toThrow(InvalidArgumentException::class, 'already in use');
});

test('user account updater syncs profile to laravel users table', function (): void {
    $env = app(Environment::class);
    $userId = (int) $env->model('res.users')->create([
        'name' => 'Before',
        'email' => 'before@velm.test',
        'password' => 'secret',
    ])->ids()[0];

    VelmPanel::auth()->login(new GenericUser(['id' => $userId, 'email' => 'before@velm.test']));
    (new UserAccountUpdater(bindSelfUserAccountEnv($userId)))->updateProfile($userId, [
        'name' => 'After',
        'email' => 'after@velm.test',
    ]);

    $row = $env->withAclBypass(fn () => $env->browse('res.users', [$userId])->read(['name', 'email'])[0]);

    expect($row['name'])->toBe('After')
        ->and($row['email'])->toBe('after@velm.test')
        ->and(DB::table('users')->where('id', $userId)->value('email'))->toBe('after@velm.test');
});

test('user account updater validates company access for non superusers', function (): void {
    $env = app(Environment::class);
    $allowed = (int) $env->model('res.company')->create(['name' => 'Allowed Co'])->ids()[0];
    $denied = (int) $env->model('res.company')->create(['name' => 'Denied Co'])->ids()[0];
    $userId = (int) $env->model('res.users')->create([
        'name' => 'Company User',
        'email' => 'company@velm.test',
        'password' => 'secret',
        'company_id' => $allowed,
    ])->ids()[0];

    VelmPanel::auth()->login(new GenericUser(['id' => $userId, 'email' => 'company@velm.test']));
    $bound = new Environment($env->connection, $env->registry, $userId);
    app()->instance(Environment::class, $bound);
    $updater = new UserAccountUpdater($bound);

    $updater->updateProfile($userId, ['company_id' => $allowed]);

    expect($bound->withAclBypass(fn () => $bound->browse('res.users', [$userId])->read(['company_id'])[0]['company_id']))
        ->toBe($allowed);

    expect(fn () => $updater->updateProfile($userId, ['company_id' => $denied]))
        ->toThrow(InvalidArgumentException::class, 'cannot use that company');
});

test('user account updater no-ops when profile payload is empty', function (): void {
    $env = app(Environment::class);
    $userId = (int) $env->model('res.users')->create([
        'name' => 'Stable',
        'email' => 'stable@velm.test',
        'password' => 'secret',
    ])->ids()[0];

    VelmPanel::auth()->login(new GenericUser(['id' => $userId, 'email' => 'stable@velm.test']));
    (new UserAccountUpdater(bindSelfUserAccountEnv($userId)))->updateProfile($userId, []);

    expect($env->withAclBypass(fn () => $env->browse('res.users', [$userId])->read(['name'])[0]['name']))->toBe('Stable');
});

test('user account updater changes password when current password matches', function (): void {
    $env = app(Environment::class);
    $userId = (int) $env->model('res.users')->create([
        'name' => 'Pwd',
        'email' => 'pwd@velm.test',
        'password' => 'old-secret',
    ])->ids()[0];
    $oldHash = (string) DB::table('users')->where('id', $userId)->value('password');

    VelmPanel::auth()->login(new GenericUser(['id' => $userId, 'email' => 'pwd@velm.test']));
    (new UserAccountUpdater(bindSelfUserAccountEnv($userId)))->changePassword($userId, 'old-secret', 'new-secret-99');

    $newHash = (string) DB::table('users')->where('id', $userId)->value('password');

    expect($newHash)->not->toBe($oldHash)
        ->and(Hash::check('new-secret-99', $newHash))->toBeTrue();
});

test('user account updater rejects wrong current password', function (): void {
    $env = app(Environment::class);
    $userId = (int) $env->model('res.users')->create([
        'name' => 'Pwd',
        'email' => 'wrong@velm.test',
        'password' => 'old-secret',
    ])->ids()[0];

    VelmPanel::auth()->login(new GenericUser(['id' => $userId, 'email' => 'wrong@velm.test']));
    $updater = new UserAccountUpdater(bindSelfUserAccountEnv($userId));

    expect(fn () => $updater->changePassword($userId, 'nope', 'new-secret-99'))
        ->toThrow(InvalidArgumentException::class, 'Current password is incorrect');
});
