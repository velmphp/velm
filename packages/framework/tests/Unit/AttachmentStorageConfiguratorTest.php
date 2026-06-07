<?php

declare(strict_types=1);

use Illuminate\Contracts\Filesystem\Filesystem;
use Velm\Framework\Storage\AttachmentStorageConfigurator;
use Velm\Framework\Storage\FlysystemAttachmentBackend;
use Velm\Framework\Tests\TestCase;
use Velm\Storage\AttachmentStorage;
use Velm\Storage\DbStorageBackend;
use Velm\Storage\LocalStorageBackend;

uses(TestCase::class);

beforeEach(function (): void {
    AttachmentStorage::resetBackendCache();
});

test('attachment storage configurator uses db backend when disk is db', function (): void {
    config([
        'velm.attachments.disk' => 'db',
        'filesystems.default' => 'local',
    ]);

    AttachmentStorageConfigurator::register($this->app);
    $backend = AttachmentStorage::backend();

    expect($backend)->toBeInstanceOf(DbStorageBackend::class);
});

test('attachment storage configurator uses db backend for legacy backend setting', function (): void {
    config([
        'velm.attachments.backend' => 'db',
        'velm.attachments.disk' => 'local',
    ]);

    AttachmentStorageConfigurator::register($this->app);
    $backend = AttachmentStorage::backend();

    expect($backend)->toBeInstanceOf(DbStorageBackend::class);
});

test('attachment storage configurator uses explicit local path backend', function (): void {
    $dir = sys_get_temp_dir().'/velm-attach-'.uniqid('', true);
    mkdir($dir, 0777, true);

    config([
        'velm.attachments.disk' => 'local-path',
        'velm.attachments.dir' => $dir,
        'velm.attachments.backend' => 'local',
    ]);

    AttachmentStorageConfigurator::register($this->app);
    $backend = AttachmentStorage::backend();

    expect($backend)->toBeInstanceOf(LocalStorageBackend::class);

    $key = $backend->save('note.txt', 'hello');

    expect(file_get_contents($dir.'/'.$key))->toBe('hello');
});

test('attachment storage configurator honors configured disk name', function (): void {
    config([
        'velm.attachments.disk' => 'attachments',
        'filesystems.disks.attachments' => [
            'driver' => 'local',
            'root' => storage_path('framework/testing/disks/attachments'),
            'throw' => false,
        ],
    ]);

    AttachmentStorageConfigurator::register($this->app);
    $backend = AttachmentStorage::backend();

    expect($backend)->toBeInstanceOf(FlysystemAttachmentBackend::class);
});

test('attachment storage configurator falls back to local root without filesystem binding', function (): void {
    $this->app->forgetInstance('filesystem');
    unset($this->app['filesystem']);

    config([
        'velm.attachments.disk' => 'local',
        'velm.attachments.dir' => sys_get_temp_dir().'/velm-fallback-'.uniqid('', true),
    ]);

    AttachmentStorageConfigurator::register($this->app);
    $backend = AttachmentStorage::backend();

    expect($backend)->toBeInstanceOf(LocalStorageBackend::class);
});
