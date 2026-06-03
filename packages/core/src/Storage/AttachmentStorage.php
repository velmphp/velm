<?php

declare(strict_types=1);

namespace Velm\Storage;

final class AttachmentStorage
{
    private static ?StorageBackend $backend = null;

    /** @var (callable(): StorageBackend)|null */
    private static $resolver = null;

    /**
     * Laravel apps register a Flysystem-backed resolver from {@see Velm\Framework\Storage\AttachmentStorageConfigurator}.
     *
     * @param  (callable(): StorageBackend)|null  $resolver
     */
    public static function resolveUsing(?callable $resolver): void
    {
        self::$resolver = $resolver;
        self::$backend = null;
    }

    public static function backend(): StorageBackend
    {
        if (self::$backend !== null) {
            return self::$backend;
        }

        if (self::$resolver !== null) {
            self::$backend = (self::$resolver)();

            return self::$backend;
        }

        $kind = strtolower((string) (self::config('backend') ?? 'local'));

        self::$backend = match ($kind) {
            'db' => new DbStorageBackend,
            'local' => new LocalStorageBackend(self::fallbackLocalRoot()),
            default => throw new \RuntimeException(
                "Unknown attachment backend '{$kind}'; expected 'local' or 'db'.",
            ),
        };

        return self::$backend;
    }

    public static function resetBackendCache(): void
    {
        self::$backend = null;
    }

    public static function fallbackLocalRoot(): string
    {
        $configured = self::config('dir');

        if (is_string($configured) && $configured !== '') {
            return rtrim($configured, '/');
        }

        if (function_exists('app')) {
            try {
                $app = app();

                if (method_exists($app, 'storagePath')) {
                    return rtrim($app->storagePath('app/velm/attachments'), '/');
                }
            } catch (\Throwable) {
            }
        }

        return sys_get_temp_dir().'/velm-attachments';
    }

    private static function config(string $key): mixed
    {
        if (! function_exists('app')) {
            return null;
        }

        try {
            $app = app();

            if (! $app->bound('config')) {
                return null;
            }

            return $app->make('config')->get("velm.attachments.{$key}");
        } catch (\Throwable) {
            return null;
        }
    }
}
