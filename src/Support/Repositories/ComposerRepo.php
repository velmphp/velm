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

    public function require(string $package, string $version = '^0.0.1'): void
    {
        // run composer require command
        $command = sprintf('composer require %s:%s', escapeshellarg($package), $version);

        // Use symfony process for better handling of the command execution
        $process = new \Symfony\Component\Process\Process(explode(' ', $command));
        $process->setTimeout(300);
        $process->enableOutput(); // Set a timeout for the process

        $process->run();
        $errorOutput = $process->getErrorOutput();
        if (! $process->isSuccessful()) {
            throw new \RuntimeException('Failed to require package: '.$errorOutput);
        }
    }

    public function remove(string $package): void
    {
        // run composer remove command
        $command = sprintf('composer remove %s', escapeshellarg($package));
        $res = $this->runShellCommand($command);
    }

    public function runShellCommand(string $command): string
    {
        $process = new \Symfony\Component\Process\Process(explode(' ', $command));
        $process->setTimeout(300);
        $process->enableOutput();

        $process->run();
        $errorOutput = $process->getErrorOutput();
        if (! $process->isSuccessful()) {
            throw new \RuntimeException('Command failed: '.$errorOutput);
        }

        return $process->getOutput();
    }
}
