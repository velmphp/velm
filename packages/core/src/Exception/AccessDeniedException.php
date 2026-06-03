<?php

declare(strict_types=1);

namespace Velm\Exception;

final class AccessDeniedException extends \RuntimeException
{
    public static function forPermission(string $model, string $perm, ?int $uid): self
    {
        if ($uid === null) {
            return new self("Access denied: {$perm} on {$model} (anonymous)");
        }

        return new self("Access denied: {$perm} on {$model} (uid={$uid})");
    }

    public static function forCompanyScope(string $model, ?int $uid): self
    {
        if ($uid === null) {
            return new self("Access denied: record outside active company on {$model} (anonymous)");
        }

        return new self("Access denied: record outside active company on {$model} (uid={$uid})");
    }

    public static function forCompanyMismatch(string $model, ?int $uid): self
    {
        if ($uid === null) {
            return new self("Access denied: company mismatch on {$model} (anonymous)");
        }

        return new self("Access denied: company mismatch on {$model} (uid={$uid})");
    }
}
