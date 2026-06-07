<?php

declare(strict_types=1);

use Velm\Storage\AttachmentStorage;
use Velm\Storage\DbStorageBackend;
use Velm\Framework\Tests\TestCase;

uses(TestCase::class);

afterEach(function (): void {
    AttachmentStorage::resolveUsing(null);
    AttachmentStorage::resetBackendCache();
});

test('attachment storage uses db backend from laravel config', function (): void {
    config(['velm.attachments.backend' => 'db', 'velm.attachments.dir' => null]);

    expect(AttachmentStorage::backend())->toBeInstanceOf(DbStorageBackend::class)
        ->and(AttachmentStorage::fallbackLocalRoot())->toContain('velm/attachments');
});

test('attachment storage fallback local root uses configured directory', function (): void {
    $dir = sys_get_temp_dir().'/velm-attach-root-'.uniqid('', true);
    config(['velm.attachments.dir' => $dir]);

    expect(AttachmentStorage::fallbackLocalRoot())->toBe(rtrim($dir, '/'));

    @rmdir($dir);
});

