<?php

declare(strict_types=1);

namespace Velm\Storage;

final class LocalStorageBackend implements StorageBackend
{
    public function __construct(
        private readonly string $root,
    ) {}

    public function save(string $name, string $content): string
    {
        $relative = AttachmentKeyBuilder::build($name);
        $absolute = $this->root.'/'.$relative;
        $directory = dirname($absolute);

        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new \RuntimeException("Could not create attachment directory: {$directory}");
        }

        if (file_put_contents($absolute, $content) === false) {
            throw new \RuntimeException("Could not write attachment file: {$absolute}");
        }

        return $relative;
    }

    public function load(string $key): string
    {
        $path = $this->resolvePath($key);

        if (! is_file($path)) {
            throw new \RuntimeException("Attachment file not found: {$key}");
        }

        $content = file_get_contents($path);

        if ($content === false) {
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
            $path = $this->resolvePath($key);
        } catch (\InvalidArgumentException) {
            return;
        }

        if (! is_file($path)) {
            return;
        }

        @unlink($path);

        foreach ([dirname($path), dirname($path, 2)] as $parent) {
            if ($parent === $this->root || ! is_dir($parent)) {
                break;
            }

            @rmdir($parent);
        }
    }

    private function resolvePath(string $key): string
    {
        AttachmentKeyBuilder::assertSafeKey($key);

        return $this->root.'/'.$key;
    }
}
