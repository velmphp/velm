<?php

declare(strict_types=1);

namespace Velm\Storage;

/**
 * Sharded relative paths for attachment blobs (PyVelm-compatible layout).
 */
final class AttachmentKeyBuilder
{
    public static function build(string $originalName): string
    {
        $unique = bin2hex(random_bytes(16));
        $shardA = substr($unique, 0, 2);
        $shardB = substr($unique, 2, 2);
        $safe = self::sanitizeFilename($originalName);

        return $shardA.'/'.$shardB.'/'.$unique.'_'.$safe;
    }

    public static function sanitizeFilename(string $name): string
    {
        $cleaned = preg_replace('/[^A-Za-z0-9._-]/', '_', trim($name)) ?? '';

        if ($cleaned === '') {
            $cleaned = 'file';
        }

        return substr($cleaned, 0, 120);
    }

    public static function assertSafeKey(string $key): void
    {
        if ($key === '' || str_starts_with($key, '/') || str_contains($key, '..')) {
            throw new \InvalidArgumentException("Invalid storage key: {$key}");
        }
    }
}
