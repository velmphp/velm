<?php

declare(strict_types=1);

namespace Velm\Modules;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ModuleRepository
{
    public const string TABLE = 'ir_module';

    public function ensureTable(): void
    {
        if (Schema::hasTable(self::TABLE)) {
            return;
        }

        Schema::create(self::TABLE, static function ($table): void {
            $table->string('name')->primary();
            $table->string('version');
            $table->timestamp('installed_at');
        });
    }

    /**
     * @return list<string>
     */
    public function installedNames(): array
    {
        $this->ensureTable();

        return DB::table(self::TABLE)
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    public function isInstalled(string $name): bool
    {
        $this->ensureTable();

        return DB::table(self::TABLE)->where('name', $name)->exists();
    }

    public function installedVersion(string $name): ?string
    {
        $this->ensureTable();

        $version = DB::table(self::TABLE)->where('name', $name)->value('version');

        return is_string($version) ? $version : null;
    }

    public function markInstalled(ModuleSpec $spec): void
    {
        $this->ensureTable();

        $now = now();

        DB::table(self::TABLE)->updateOrInsert(
            ['name' => $spec->name],
            [
                'version' => $spec->versionString(),
                'installed_at' => $now,
            ],
        );
    }

    public function markUninstalled(string $name): void
    {
        $this->ensureTable();

        DB::table(self::TABLE)->where('name', $name)->delete();
    }
}
