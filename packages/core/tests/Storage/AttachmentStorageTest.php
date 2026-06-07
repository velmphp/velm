<?php

declare(strict_types=1);

use Velm\Storage\AttachmentStorage;
use Velm\Storage\DbStorageBackend;
use Velm\Storage\LocalStorageBackend;

afterEach(function (): void {
    AttachmentStorage::resolveUsing(null);
    AttachmentStorage::resetBackendCache();
});

test('local storage backend shards and round-trips bytes', function (): void {
    $root = sys_get_temp_dir().'/velm-storage-test-'.uniqid('', true);
    mkdir($root, 0775, true);

    $backend = new LocalStorageBackend($root);
    $key = $backend->save('My Report.pdf', '%PDF-1.4');
    $loaded = $backend->load($key);

    expect($loaded)->toBe('%PDF-1.4')
        ->and($key)->toContain('_My_Report.pdf')
        ->and(substr_count($key, '/'))->toBe(2);

    $backend->delete($key);

    expect(is_file($root.'/'.$key))->toBeFalse();
});

test('local storage backend load throws when file is missing', function (): void {
    $root = sys_get_temp_dir().'/velm-storage-missing-'.uniqid('', true);
    mkdir($root, 0775, true);

    $backend = new LocalStorageBackend($root);

    expect(fn () => $backend->load('aa/bb/cc/missing.txt'))
        ->toThrow(RuntimeException::class, 'Attachment file not found');
});

test('local storage backend delete ignores empty and unsafe keys', function (): void {
    $root = sys_get_temp_dir().'/velm-storage-delete-'.uniqid('', true);
    mkdir($root, 0775, true);

    $backend = new LocalStorageBackend($root);

    $backend->delete('');
    $backend->delete('../escape');
    $backend->delete('aa/bb/cc/absent.txt');

    expect(true)->toBeTrue();
});

test('attachment storage resolveUsing and resetBackendCache', function (): void {
    $first = new LocalStorageBackend(sys_get_temp_dir().'/velm-a-'.uniqid('', true));
    $second = new DbStorageBackend;

    AttachmentStorage::resolveUsing(fn (): LocalStorageBackend => $first);
    expect(AttachmentStorage::backend())->toBe($first);

    AttachmentStorage::resolveUsing(fn (): DbStorageBackend => $second);
    AttachmentStorage::resetBackendCache();

    expect(AttachmentStorage::backend())->toBe($second);
});

test('attachment storage fallbackLocalRoot uses temp when unconfigured', function (): void {
    AttachmentStorage::resolveUsing(null);
    AttachmentStorage::resetBackendCache();

    expect(AttachmentStorage::fallbackLocalRoot())->toContain('velm');
});

test('attachment storage defaults to local backend without resolver', function (): void {
    AttachmentStorage::resolveUsing(null);
    AttachmentStorage::resetBackendCache();

    expect(AttachmentStorage::backend())->toBeInstanceOf(LocalStorageBackend::class);
});

