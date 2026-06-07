<?php

declare(strict_types=1);

use Velm\Storage\DbStorageBackend;

test('db storage backend save returns empty key for inline datas column', function (): void {
    expect((new DbStorageBackend)->save('file.txt', 'bytes'))->toBe('');
});

test('db storage backend load throws because bytes live on row', function (): void {
    expect(fn () => (new DbStorageBackend)->load('any-key'))
        ->toThrow(RuntimeException::class, 'DbStorageBackend has no out-of-band bytes');
});

test('db storage backend delete is a no-op', function (): void {
    (new DbStorageBackend)->delete('any-key');

    expect(true)->toBeTrue();
});
