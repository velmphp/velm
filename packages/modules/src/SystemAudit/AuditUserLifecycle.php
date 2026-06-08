<?php

declare(strict_types=1);

namespace Velm\Modules\SystemAudit;

use Velm\Environment;

final class AuditUserLifecycle
{
    /**
     * @param  array<string, mixed>  $detail
     */
    public static function log(
        Environment $env,
        int $userId,
        string $event,
        array $detail = [],
    ): void {
        if (! $env->registry->has('ir.user.lifecycle')) {
            return;
        }

        $env->withAclBypass(function () use ($env, $userId, $event, $detail): void {
            $env->model('ir.user.lifecycle')->create([
                'user_id' => $userId,
                'event' => $event,
                'detail' => $detail !== [] ? json_encode($detail, JSON_THROW_ON_ERROR) : null,
                'actor_id' => $env->uid,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  array<string, mixed>  $before
     */
    public static function trackWrite(Environment $env, int $userId, array $values, array $before = []): void
    {
        if ($values === []) {
            return;
        }

        $row = $before;

        if (array_key_exists('active', $values)) {
            $wasActive = (bool) ($row['active'] ?? true);
            $nowActive = (bool) $values['active'];

            if ($wasActive !== $nowActive) {
                self::log($env, $userId, $nowActive ? 'activated' : 'deactivated', [
                    'active' => $nowActive,
                ]);
            }
        }

        if (array_key_exists('password', $values)) {
            self::log($env, $userId, 'password_changed');
        }

        if (array_key_exists('group_ids', $values)) {
            self::log($env, $userId, 'groups_changed', [
                'before' => $row['group_ids'] ?? [],
                'after' => $values['group_ids'],
            ]);
        }
    }

    public static function trackCreate(Environment $env, int $userId, array $values): void
    {
        self::log($env, $userId, 'created', [
            'name' => $values['name'] ?? null,
            'email' => $values['email'] ?? null,
            'active' => $values['active'] ?? true,
        ]);
    }

    public static function trackDelete(Environment $env, int $userId): void
    {
        self::log($env, $userId, 'deleted');
    }
}
