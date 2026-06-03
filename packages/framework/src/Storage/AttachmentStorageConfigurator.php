<?php

declare(strict_types=1);

namespace Velm\Framework\Storage;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Storage;
use Velm\Storage\AttachmentStorage;
use Velm\Storage\DbStorageBackend;
use Velm\Storage\LocalStorageBackend;
use Velm\Storage\StorageBackend;

final class AttachmentStorageConfigurator
{
    public static function register(Application $app): void
    {
        AttachmentStorage::resolveUsing(static function () use ($app): StorageBackend {
            return self::makeBackend($app);
        });
    }

    private static function makeBackend(Application $app): StorageBackend
    {
        $disk = self::resolveDiskName($app);
        $legacyBackend = strtolower((string) $app->make('config')->get('velm.attachments.backend', ''));

        if ($disk === 'db' || $legacyBackend === 'db') {
            return new DbStorageBackend;
        }

        $explicitDir = $app->make('config')->get('velm.attachments.dir');

        if (
            is_string($explicitDir) && $explicitDir !== ''
            && ($legacyBackend === 'local' || $disk === 'local-path')
        ) {
            return new LocalStorageBackend(rtrim($explicitDir, '/'));
        }

        if ($app->bound('filesystem')) {
            return new FlysystemAttachmentBackend(Storage::disk($disk));
        }

        return new LocalStorageBackend(AttachmentStorage::fallbackLocalRoot());
    }

    private static function resolveDiskName(Application $app): string
    {
        $configured = $app->make('config')->get('velm.attachments.disk');

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        return (string) $app->make('config')->get('filesystems.default', 'local');
    }
}
