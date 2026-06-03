<?php

declare(strict_types=1);

namespace Velm\Framework\Storage;

use Illuminate\Contracts\Filesystem\Filesystem;
use Velm\Storage\AttachmentKeyBuilder;
use Velm\Storage\StorageBackend;

/**
 * Attachment blobs via a Laravel filesystem disk (Flysystem underneath).
 */
final class FlysystemAttachmentBackend implements StorageBackend
{
    public function __construct(
        private readonly Filesystem $disk,
    ) {}

    public function save(string $name, string $content): string
    {
        $key = AttachmentKeyBuilder::build($name);

        if (! $this->disk->put($key, $content)) {
            throw new \RuntimeException("Could not write attachment to disk at {$key}.");
        }

        return $key;
    }

    public function load(string $key): string
    {
        AttachmentKeyBuilder::assertSafeKey($key);

        if (! $this->disk->exists($key)) {
            throw new \RuntimeException("Attachment file not found: {$key}");
        }

        $content = $this->disk->get($key);

        if ($content === null) {
            throw new \RuntimeException("Could not read attachment file: {$key}");
        }

        return $content;
    }

    public function delete(string $key): void
    {
        if ($key === '') {
            return;
        }

        try {
            AttachmentKeyBuilder::assertSafeKey($key);
        } catch (\InvalidArgumentException) {
            return;
        }

        $this->disk->delete($key);
    }
}
