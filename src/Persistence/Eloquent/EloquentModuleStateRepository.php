<?php

namespace Velm\Core\Persistence\Eloquent;

use Velm\Core\Persistence\Contracts\ModuleStateRepository;
use Velm\Core\Persistence\ModuleState;

class EloquentModuleStateRepository implements ModuleStateRepository
{
    public function all(?string $tenant = null): array
    {
        // if the velm_modules table does not exist, return an empty array
        $query = VelmModuleRecord::query();

        if (filled($tenant)) {
            $query->where('tenant', $tenant);
        }

        $records = $query->get();

        return $records->mapWithKeys(function (VelmModuleRecord $record) {
            return [$record->getAttribute('package') => $this->toDomain($record)];
        })->all();
    }

    public function get(string $package, ?string $tenant = null): ?ModuleState
    {
        $record = VelmModuleRecord::query()->findForTenant($package, $tenant)->first();

        return $record ? $this->toDomain($record) : null;
    }

    public function install(string $package, ?string $tenant = null): ModuleState
    {
        $descriptor = velm()->registry()->modules()->all()[$package] ?? null;
        $record = VelmModuleRecord::query()->firstOrCreate(
            [
                'package' => $package,
                'tenant' => $tenant,
            ],
            [
                'version' => $descriptor->version,
                'is_enabled' => true,
                'enabled_at' => now(),
            ]
        );

        return $this->toDomain($record);
    }

    public function enable(string $package, ?string $tenant = null): void
    {
        $record = VelmModuleRecord::query()->findForTenant($package, $tenant)->firstOrFail();
        $record->update([
            'is_enabled' => true,
            'enabled_at' => now(),
            'disabled_at' => null,
        ]);
    }

    public function disable(string $package, ?string $tenant = null): void
    {
        $record = VelmModuleRecord::query()->findForTenant($package, $tenant)->firstOrFail();
        $record->update([
            'is_enabled' => false,
            'disabled_at' => now(),
            'enabled_at' => null,
        ]);
    }

    public function upgrade(string $package, ?string $tenant = null): void
    {
        $descriptor = velm()->registry()->modules()->all()[$package] ?? null;
        $record = VelmModuleRecord::query()->findForTenant($package, $tenant)->firstOrFail();
        if ($record->disabled) {
            throw new \RuntimeException("Cannot upgrade a disabled module: {$package}");
        }
        $record->update([
            'version' => $descriptor->version,
        ]);
    }

    public function uninstall(string $package, ?string $tenant = null): void
    {
        $record = VelmModuleRecord::query()->findForTenant($package, $tenant)->firstOrFail();
        $record->delete();
    }

    private function toDomain(VelmModuleRecord $record): ModuleState
    {
        return new ModuleState(
            package: $record->getAttribute('package'),
            version: $record->getAttribute('version'),
            installedAt: $record->getAttribute('created_at')->toDateTimeImmutable(),
            tenant: $record->getAttribute('tenant'),
            isEnabled: $record->getAttribute('is_enabled'),
            updatedAt: $record->getAttribute('updated_at')->toDateTimeImmutable(),
            enabledAt: ($enabledAt = $record->getAttribute('enabled_at')) ? $enabledAt->toDateTimeImmutable() : null,
            disabledAt: ($disabledAt = $record->getAttribute('disabled_at')) ? $disabledAt->toDateTimeImmutable() : null,
        );
    }
}
