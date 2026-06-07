<?php

declare(strict_types=1);

use Velm\Modules\FileManager\FileIconKey;

test('file icon key resolves mime prefixes and exact matches', function (): void {
    expect(FileIconKey::resolve('image/png'))->toBe('image')
        ->and(FileIconKey::resolve('application/pdf'))->toBe('pdf')
        ->and(FileIconKey::resolve('application/json'))->toBe('json')
        ->and(FileIconKey::resolve('text/plain'))->toBe('text')
        ->and(FileIconKey::resolve('audio/mpeg'))->toBe('audio')
        ->and(FileIconKey::resolve('video/mp4'))->toBe('video')
        ->and(FileIconKey::resolve('application/zip'))->toBe('zip');
});

test('file icon key resolves from filename extension', function (): void {
    expect(FileIconKey::resolve(null, 'report.PDF'))->toBe('pdf')
        ->and(FileIconKey::resolve('application/octet-stream', 'notes.md'))->toBe('text')
        ->and(FileIconKey::resolve(null, 'song.mp3'))->toBe('audio')
        ->and(FileIconKey::resolve(null, 'clip.mkv'))->toBe('video')
        ->and(FileIconKey::resolve(null, 'archive.7z'))->toBe('zip');
});

test('file icon key falls back to generic file', function (): void {
    expect(FileIconKey::resolve('application/octet-stream', 'blob.bin'))->toBe('file')
        ->and(FileIconKey::resolve(null, null))->toBe('file');
});
