<?php

declare(strict_types=1);

namespace Velm\Storage;

/**
 * Minimal blob storage protocol for {@see ir.attachment}.
 */
interface StorageBackend
{
    /**
     * Persist bytes and return an opaque storage key (empty for inline DB storage).
     */
    public function save(string $name, string $content): string;

    public function load(string $key): string;

    public function delete(string $key): void;
}
