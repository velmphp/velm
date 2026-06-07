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

test('flysystem backend throws when disk put fails', function (): void {
    $disk = test()->createMock(\Illuminate\Contracts\Filesystem\Filesystem::class);
    $disk->method('put')->willReturn(false);

    $backend = new FlysystemAttachmentBackend($disk);

    expect(fn () => $backend->save('report.pdf', '%PDF'))
        ->toThrow(\RuntimeException::class, 'Could not write attachment');
});

test('flysystem backend throws when attachment file is missing', function (): void {
    $disk = test()->createMock(\Illuminate\Contracts\Filesystem\Filesystem::class);
    $disk->method('exists')->willReturn(false);

    $backend = new FlysystemAttachmentBackend($disk);

    expect(fn () => $backend->load('aa/bb/cc/report.pdf'))
        ->toThrow(\RuntimeException::class, 'Attachment file not found');
});

test('flysystem backend throws when disk get returns null', function (): void {
    $disk = test()->createMock(\Illuminate\Contracts\Filesystem\Filesystem::class);
    $disk->method('exists')->willReturn(true);
    $disk->method('get')->willReturn(null);

    $backend = new FlysystemAttachmentBackend($disk);

    expect(fn () => $backend->load('aa/bb/cc/report.pdf'))
        ->toThrow(\RuntimeException::class, 'Could not read attachment file');
});

test('flysystem backend delete ignores empty key', function (): void {
    $disk = test()->createMock(\Illuminate\Contracts\Filesystem\Filesystem::class);
    $disk->expects(test()->never())->method('delete');

    $backend = new FlysystemAttachmentBackend($disk);
    $backend->delete('');

    expect(true)->toBeTrue();
});

test('flysystem backend delete ignores unsafe key', function (): void {
    $disk = test()->createMock(\Illuminate\Contracts\Filesystem\Filesystem::class);
    $disk->expects(test()->never())->method('delete');

    $backend = new FlysystemAttachmentBackend($disk);
    $backend->delete('../escape.txt');

    expect(true)->toBeTrue();
});
