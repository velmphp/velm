<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use Velm\Framework\Storage\FlysystemAttachmentBackend;
use Velm\Framework\Tests\TestCase;
use Velm\Storage\AttachmentStorage;

uses(TestCase::class);

beforeEach(function (): void {
    Storage::fake('local');
    AttachmentStorage::resetBackendCache();
    config(['filesystems.default' => 'local']);
});

test('flysystem disk stores and removes sharded attachment keys', function (): void {
    $backend = new FlysystemAttachmentBackend(Storage::disk('local'));
    $key = $backend->save('report.pdf', '%PDF');

    expect($backend->load($key))->toBe('%PDF')
        ->and(Storage::disk('local')->exists($key))->toBeTrue();

    $backend->delete($key);

    expect(Storage::disk('local')->exists($key))->toBeFalse();
});

test('attachment storage configurator uses laravel default filesystem disk', function (): void {
    AttachmentStorage::resetBackendCache();

    $backend = AttachmentStorage::backend();

    expect($backend)->toBeInstanceOf(FlysystemAttachmentBackend::class);

    $key = $backend->save('note.txt', 'hello');

    expect(Storage::disk('local')->get($key))->toBe('hello');
});
