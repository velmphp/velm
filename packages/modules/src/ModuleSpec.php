<?php

declare(strict_types=1);

namespace Velm\Modules;

final readonly class ModuleSpec
{
    /**
     * @param  list<int>  $version
     * @param  list<string>  $depends
     * @param  list<string>  $data
     */
    public function __construct(
        public string $name,
        public array $version,
        public array $depends,
        public string $path,
        public array $data = [],
        public string $summary = '',
        public string $description = '',
        public string $category = '',
        public string $author = '',
        public string $icon = '',
    ) {}

    /**
     * @param  array<string, mixed>  $manifest
     */
    public static function fromManifest(array $manifest, string $path): self
    {
        if (! isset($manifest['NAME']) || ! is_string($manifest['NAME']) || $manifest['NAME'] === '') {
            throw new \InvalidArgumentException("Manifest at {$path} is missing a non-empty NAME.");
        }

        if (! isset($manifest['VERSION']) || ! is_array($manifest['VERSION']) || $manifest['VERSION'] === []) {
            throw new \InvalidArgumentException("Manifest for {$manifest['NAME']} is missing VERSION.");
        }

        $version = array_map(static fn (mixed $part): int => (int) $part, $manifest['VERSION']);
        $depends = array_values(array_map('strval', $manifest['DEPENDS'] ?? []));
        $data = array_values(array_map('strval', $manifest['DATA'] ?? []));

        return new self(
            name: $manifest['NAME'],
            version: $version,
            depends: $depends,
            path: $path,
            data: $data,
            summary: (string) ($manifest['SUMMARY'] ?? ''),
            description: (string) ($manifest['DESCRIPTION'] ?? ''),
            category: (string) ($manifest['CATEGORY'] ?? ''),
            author: (string) ($manifest['AUTHOR'] ?? ''),
            icon: (string) ($manifest['ICON'] ?? ''),
        );
    }

    public function versionString(): string
    {
        return implode('.', array_map('strval', $this->version));
    }

    public function displayName(): string
    {
        return str_replace('_', ' ', ucwords($this->name, '_'));
    }
}
