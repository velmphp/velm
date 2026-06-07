<?php

declare(strict_types=1);

namespace Velm\Storage;

final class AttachmentStorage
{
    private static ?StorageBackend $backend = null;

    /** @var (callable(): StorageBackend)|null */
    private static $resolver = null;

    /** @var (callable(): object)|null */
    private static $testAppFactory = null;

    private static bool $forceNoAppFunction = false;

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

    /**
     * @param  (callable(): object)|null  $factory
     */
    public static function useAppFactoryForTesting(?callable $factory): void
    {
        self::$testAppFactory = $factory;
    }

    public static function forceNoAppFunctionForTesting(bool $value): void
    {
        self::$forceNoAppFunction = $value;
    }

    public static function resetTestingState(): void
    {
        self::$testAppFactory = null;
        self::$forceNoAppFunction = false;
        self::resetBackendCache();
        self::resolveUsing(null);
    }

    public static function fallbackLocalRoot(): string
    {
        $configured = self::config('dir');

        if (is_string($configured) && $configured !== '') {
            return rtrim($configured, '/');
        }

        $app = self::appInstance();

        if ($app !== null) {
            try {
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
        if (! self::appFunctionExists()) {
            return null;
        }

        try {
            $app = self::appInstance();

            if ($app === null) {
                return null;
            }

            if (! $app->bound('config')) {
                return null;
            }

            return $app->make('config')->get("velm.attachments.{$key}");
        } catch (\Throwable) {
            return null;
        }
    }

    private static function appFunctionExists(): bool
    {
        if (self::$forceNoAppFunction) {
            return false;
        }

        return self::$testAppFactory !== null || function_exists('app');
    }

    private static function appInstance(): ?object
    {
        if (self::$testAppFactory !== null) {
            return (self::$testAppFactory)();
        }

        if (! function_exists('app')) {
            return null;
        }

        try {
            return app();
        } catch (\Throwable) {
            return null;
        }
    }
}
