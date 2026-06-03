<?php

declare(strict_types=1);

namespace Velm\Storage;

/**
 * Inline storage — bytes live in the row's {@code datas} column.
 */
final class DbStorageBackend implements StorageBackend
{
    public function save(string $name, string $content): string
    {
        return '';
    }

    public function load(string $key): string
    {
        throw new \RuntimeException(
            'DbStorageBackend has no out-of-band bytes; read datas from the row.',
        );
    }

    public function delete(string $key): void {}
}
