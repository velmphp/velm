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
}
