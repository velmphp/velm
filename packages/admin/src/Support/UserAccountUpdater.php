<?php

declare(strict_types=1);

namespace Velm\Admin\Support;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Velm\Environment;
use Velm\Modules\Base\Models\User;

/**
 * Self-service updates for the signed-in panel user (Laravel {@code users} + {@code res.users}).
 */
final class UserAccountUpdater
{
    public function __construct(
        private readonly Environment $env,
    ) {}

    /**
     * @param  array{name?: string, email?: string, company_id?: int|null}  $values
     */
    public function updateProfile(int $userId, array $values): void
    {
        $this->assertSelf($userId);

        $updates = [];

        if (array_key_exists('name', $values)) {
            $name = trim((string) $values['name']);

            if ($name === '') {
                throw new InvalidArgumentException(__('Name is required.'));
            }

            $updates['name'] = $name;
        }

        if (array_key_exists('email', $values)) {
            $email = strtolower(trim((string) $values['email']));

            if ($email === '') {
                throw new InvalidArgumentException(__('Email is required.'));
            }

            if ($this->emailTaken($email, $userId)) {
                throw new InvalidArgumentException(__('That email is already in use.'));
            }

            $updates['email'] = $email;
        }

        if (array_key_exists('company_id', $values)) {
            $companyId = $values['company_id'];

            if ($companyId === null || $companyId === '') {
                $updates['company_id'] = null;
            } else {
                $id = (int) $companyId;

                if ($id <= 0 || ! $this->canUseCompany($id)) {
                    throw new InvalidArgumentException(__('You cannot use that company.'));
                }

                $updates['company_id'] = $id;
            }
        }

        if ($updates === []) {
            return;
        }

        $this->env->withAclBypass(function () use ($userId, $updates): void {
            $this->env->browse('res.users', [$userId])->write($updates);
        });

        $this->syncLaravelProfile($userId, $updates);
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword): void
    {
        $this->assertSelf($userId);

        $hash = $this->passwordHash($userId);

        if ($hash === null || ! Hash::check($currentPassword, $hash)) {
            throw new InvalidArgumentException(__('Current password is incorrect.'));
        }

        $prepared = User::prepareValues(['password' => $newPassword], 'write');
        $stored = (string) ($prepared['password'] ?? '');

        if ($stored === '') {
            throw new InvalidArgumentException(__('New password is required.'));
        }

        $this->env->withAclBypass(function () use ($userId, $stored): void {
            $this->env->browse('res.users', [$userId])->write(['password' => $stored]);
        });

        DB::table('users')->where('id', $userId)->update(['password' => $stored]);

        $authUser = VelmPanel::auth()->user();

        if ($authUser instanceof Authenticatable && method_exists($authUser, 'forceFill')) {
            $authUser->forceFill(['password' => $stored]);
        }
    }

    private function assertSelf(int $userId): void
    {
        if ($userId <= 0) {
            throw new AuthenticationException;
        }

        $authId = VelmPanel::auth()->id();

        if (! is_numeric($authId) || (int) $authId !== $userId) {
            throw new AuthenticationException;
        }
    }

    private function emailTaken(string $email, int $ignoreUserId): bool
    {
        return $this->env->withAclBypass(function () use ($email, $ignoreUserId): bool {
            $matches = $this->env->model('res.users')->search([['email', '=', $email]], limit: 2);

            foreach ($matches->ids() as $id) {
                if ((int) $id !== $ignoreUserId) {
                    return true;
                }
            }

            return false;
        });
    }

    private function canUseCompany(int $companyId): bool
    {
        if ($this->env->isSuperuser()) {
            return $this->env->companyExists($companyId);
        }

        return in_array($companyId, $this->env->allowedCompanyIds(), true);
    }

    private function passwordHash(int $userId): ?string
    {
        $hash = DB::table('users')->where('id', $userId)->value('password');

        return is_string($hash) && $hash !== '' ? $hash : null;
    }

    /**
     * @param  array<string, mixed>  $updates
     */
    private function syncLaravelProfile(int $userId, array $updates): void
    {
        $laravel = [];

        if (isset($updates['name']) && is_string($updates['name'])) {
            $laravel['name'] = $updates['name'];
        }

        if (isset($updates['email']) && is_string($updates['email'])) {
            $laravel['email'] = $updates['email'];
        }

        if ($laravel !== []) {
            DB::table('users')->where('id', $userId)->update($laravel);
        }

        $authUser = VelmPanel::auth()->user();

        if ($authUser instanceof Authenticatable && method_exists($authUser, 'forceFill')) {
            $authUser->forceFill($laravel);
        }
    }
}
