<?php

declare(strict_types=1);

use Velm\Storage\AttachmentStorage;
use Velm\Storage\LocalStorageBackend;

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
