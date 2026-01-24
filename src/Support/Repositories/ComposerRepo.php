<?php

namespace Velm\Core\Support\Repositories;

class ComposerRepo
{
    /** @var array<string, array<string, mixed>>|null */
    private ?array $_installed = null;

    /**
     * @throws \JsonException
     */
    public function getComposerJson(string $packageName): ?array
    {
        $path = $this->getPackagePath($packageName);
        if (empty($path)) {
            return null;
        }
        $composerJsonPath = $path.'/composer.json';
        if (! file_exists($composerJsonPath)) {
            return null;
        }

        return json_decode(
            file_get_contents($composerJsonPath),
            true,
            flags: JSON_THROW_ON_ERROR
        );
    }

    /**
     * @throws \JsonException
     */
    public function getPackagePath(string $packageName): ?string
    {
        $installed = $this->getInstalledPackages();

        $installedJson = $installed[$packageName] ?? null;
        if (empty($installedJson)) {
            return null;
        }
        $relativePath = $installedJson['install-path'] ?? null;
        if (empty($relativePath)) {
            return null;
        }

        return realpath(base_path('vendor/composer/'.$relativePath));
    }

    /**
     * @throws \JsonException
     */
    public function getInstalledPackages(): array
    {
        return $this->_installed ??= collect(json_decode(
            file_get_contents(base_path('vendor/composer/installed.json')),
            true,
            flags: JSON_THROW_ON_ERROR
        )['packages'] ?? [])->keyBy('name')->toArray();
    }

    /**
     * @throws \JsonException
     */
    public function isVelmModule(string $packageName): bool
    {
        $package = $this->getComposerJson($packageName);
        if (empty($package)) {
            return false;
        }
        // Check if the type is velm-module
        if (! (isset($package['type']) && $package['type'] === 'velm-module')) {
            return false;
        }
        // Check if extra.module is set
        if (! isset($package['extra']['velm']['module'])) {
            return false;
        }
        // Check if extra.module is an instance of VelmModule
        $entryClass = $package['extra']['velm']['module'];
        if (! $entryClass) {
            return false;
        }

        // Skip checking validity of class due to its performance expense
        return true;
    }

    public function require(string $package, string $version = '*'): void
    {
        // run composer require command
        $command = sprintf('composer require %s:%s', escapeshellarg($package), escapeshellarg($version));
        exec($command, $output, $returnVar);
        if ($returnVar !== 0) {
            throw new \RuntimeException('Failed to require package: '.implode("\n", $output));
        }
    }

    public function remove(string $package): void
    {
        // run composer remove command
        $command = sprintf('composer remove %s', escapeshellarg($package));
        exec($command, $output, $returnVar);
        if ($returnVar !== 0) {
            throw new \RuntimeException('Failed to remove package: '.implode("\n", $output));
        }
    }
}
