<?php

declare(strict_types=1);

namespace Velm\Framework\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Velm\Environment;
use Velm\Framework\VelmManager;

/**
 * Keeps Velm ACL fields on {@code res.users} aligned with the authenticated Laravel user row.
 */
final class UserProvisioner
{
    public static function ensureProfile(Authenticatable $user): void
    {
        if (! app()->bound(VelmManager::class)) {
            return;
        }

        $id = $user->getAuthIdentifier();

        if (! is_numeric($id) || (int) $id <= 0) {
            return;
        }

        $env = app(VelmManager::class)->environment();

        if (! $env->registry->has('res.users')) {
            return;
        }

        $env->withAclBypass(function () use ($env, $user, $id): void {
            $record = $env->browse('res.users', [(int) $id]);

            if ($record->count() === 0) {
                return;
            }

            $updates = [];

            $name = self::stringAttribute($user, 'name');

            if ($name !== null && $name !== '') {
                $updates['name'] = $name;
            }

            $email = self::stringAttribute($user, 'email');

            if ($email !== null && $email !== '') {
                $updates['email'] = $email;
            }

            if ($updates !== []) {
                $record->write($updates);
            }
        });
    }

    /**
     * After {@see \Database\Seeders\VelmSeeder} creates the panel user, attach Velm groups/company.
     */
    public static function bootstrapAdminProfile(Environment $env, string $email): void
    {
        if (! $env->registry->has('res.users') || ! $env->registry->has('res.groups')) {
            return;
        }

        $env->withAclBypass(function () use ($env, $email): void {
            $users = $env->model('res.users')->search([['email', '=', $email]], limit: 1);

            if ($users->count() === 0) {
                return;
            }

            $adminGroup = $env->model('res.groups')->search([['name', '=', 'Admin']], limit: 1);
            $company = $env->model('res.company')->search(limit: 1);

            $values = ['active' => true];

            if ($adminGroup->count() > 0) {
                $values['group_ids'] = $adminGroup->ids();
            }

            if ($company->count() > 0) {
                $values['company_id'] = $company->ids()[0];
            }

            $users->write($values);
        });
    }

    private static function stringAttribute(Authenticatable $user, string $key): ?string
    {
        if (method_exists($user, 'getAttribute')) {
            $value = $user->getAttribute($key);

            return is_string($value) ? $value : null;
        }

        return null;
    }
}
