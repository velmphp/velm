<?php

namespace Velm\Core\Persistence;

use Velm\Core\Persistence\Contracts\ModuleStateRepository;

class JsonModuleStateRepository implements ModuleStateRepository
{
    private function getPath(): string
    {
        return storage_path('velm_modules.json');
    }

    public function all(?string $tenant = null): array
    {
        $path = $this->getPath();
        if (! file_exists($path)) {
            return [];
        }
        $json = file_get_contents($path);
        $data = json_decode($json, true);
        $result = [];
        foreach ($data as $package => $state) {
            if ($tenant === null || ($state['tenant'] ?? null) === $tenant) {
                $result[$package] = new ModuleState(
                    package: $package,
                    version: $state['version'] ?? null,
                    installedAt: $state['installed_at'] ?? null,
                    tenant: $state['tenant'] ?? null,
                    isEnabled: $state['is_enabled'] ?? false,
                    updatedAt: $state['updated_at'] ?? null,
                    enabledAt: $state['is_enabled'] ?? false,
                    disabledAt: $state['is_disabled'] ?? false,
                );
            }
        }

        return $result;
    }

    public function get(string $package, ?string $tenant = null): ?ModuleState
    {
        $all = $this->all($tenant);

        return $all[$package] ?? null;
    }

    public function install(string $package, ?string $tenant = null): ModuleState
    {
        // Find existing states
        $existing = $this->get($package, $tenant);
        if ($existing !== null) {
            return $existing;
        }
        $path = $this->getPath();
        $data = [];
        if (file_exists($path)) {
            $json = file_get_contents($path);
            $data = json_decode($json, true);
        }
        $data[$package] = [
            'package' => $package,
            'version' => velm()->registry()->modules()->all()[$package]->version ?? null,
            'installed_at' => now()->toDateTimeString(),
            'tenant' => $tenant,
            'is_enabled' => true,
            'enabled_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ];
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));

        return new ModuleState(
            package: $package,
            version: $data[$package]['version'],
            installedAt: now(),
            tenant: $tenant,
            isEnabled: true,
            updatedAt: now(),
            enabledAt: now(),
            disabledAt: null,
        );
    }

    public function enable(string $package, ?string $tenant = null): void
    {
        $path = $this->getPath();
        if (! file_exists($path)) {
            throw new \RuntimeException("Module not installed: {$package}");
        }
        $existing = $this->get($package, $tenant);
        if ($existing === null) {
            throw new \RuntimeException("Module not installed: {$package}");
        }
        $json = file_get_contents($path);
        $data = json_decode($json, true);
        $data[$package]['is_enabled'] = true;
        $data[$package]['enabled_at'] = now()->toDateTimeString();
        $data[$package]['disabled_at'] = null;
        $data[$package]['updated_at'] = now()->toDateTimeString();
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function disable(string $package, ?string $tenant = null): void
    {
        $path = $this->getPath();
        if (! file_exists($path)) {
            throw new \RuntimeException("Module not installed: {$package}");
        }
        $existing = $this->get($package, $tenant);
        if ($existing === null) {
            throw new \RuntimeException("Module not installed: {$package}");
        }
        $json = file_get_contents($path);
        $data = json_decode($json, true);
        $data[$package]['is_enabled'] = false;
        $data[$package]['disabled_at'] = now()->toDateTimeString();
        $data[$package]['enabled_at'] = null;
        $data[$package]['updated_at'] = now()->toDateTimeString();
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function upgrade(string $package, ?string $tenant = null): void
    {
        $path = $this->getPath();
        if (! file_exists($path)) {
            throw new \RuntimeException("Module not installed: {$package}");
        }
        $existing = $this->get($package, $tenant);
        if ($existing === null) {
            throw new \RuntimeException("Module not installed: {$package}");
        }
        $json = file_get_contents($path);
        $data = json_decode($json, true);
        $data[$package]['version'] = velm()->registry()->modules()->all()[$package]->version ?? $existing->version;
        $data[$package]['updated_at'] = now()->toDateTimeString();
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function uninstall(string $package, ?string $tenant = null): void
    {
        $path = $this->getPath();
        if (! file_exists($path)) {
            throw new \RuntimeException("Module not installed: {$package}");
        }
        $existing = $this->get($package, $tenant);
        if ($existing === null) {
            throw new \RuntimeException("Module not installed: {$package}");
        }
        $json = file_get_contents($path);
        $data = json_decode($json, true);
        unset($data[$package]);
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
    }
}
