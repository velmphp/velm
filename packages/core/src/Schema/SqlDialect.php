<?php

declare(strict_types=1);

namespace Velm\Schema;

use Velm\Database\Connection;

/**
 * SQL fragments and introspection helpers per database driver.
 */
final class SqlDialect
{
    public function __construct(
        private readonly string $driver,
    ) {}

    public static function for(Connection $connection): self
    {
        return new self($connection->driver());
    }

    public function driver(): string
    {
        return $this->driver;
    }

    public function idColumnSql(): string
    {
        return match ($this->driver) {
            'pgsql' => '"id" SERIAL PRIMARY KEY',
            'mysql' => '"id" BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            default => '"id" INTEGER PRIMARY KEY AUTOINCREMENT',
        };
    }

    public function supportsInformationSchema(): bool
    {
        return in_array($this->driver, ['mysql', 'pgsql'], true);
    }

    public function supportsPostgresAlterColumn(): bool
    {
        return $this->driver === 'pgsql';
    }

    public function currentDatabase(Connection $connection): ?string
    {
        return match ($this->driver) {
            'mysql' => $this->scalar($connection, 'SELECT DATABASE() as db', 'db'),
            'pgsql' => $this->scalar($connection, 'SELECT current_database() as db', 'db'),
            default => null,
        };
    }

    /**
     * @return list<string>
     */
    public function tableColumns(Connection $connection, string $table): array
    {
        if ($this->driver === 'sqlite') {
            try {
                $rows = $connection->fetchAll('PRAGMA table_info("'.$table.'")');

                if ($rows !== []) {
                    return array_values(array_map(
                        static fn (array $row): string => (string) ($row['name'] ?? ''),
                        $rows,
                    ));
                }
            } catch (\Throwable) {
                return [];
            }
        }

        if (! $this->supportsInformationSchema()) {
            return [];
        }

        $schema = $this->driver === 'pgsql'
            ? 'public'
            : $this->currentDatabase($connection);

        if ($schema === null) {
            return [];
        }

        $rows = $connection->fetchAll(
            'SELECT column_name FROM information_schema.columns WHERE table_schema = ? AND table_name = ?',
            [$schema, $table],
        );

        return array_values(array_map(
            static fn (array $row): string => (string) ($row['column_name'] ?? $row['COLUMN_NAME'] ?? ''),
            $rows,
        ));
    }

    private function scalar(Connection $connection, string $sql, string $key): ?string
    {
        $row = $connection->fetchOne($sql);

        if ($row === null) {
            return null;
        }

        $value = $row[$key] ?? null;

        return is_string($value) ? $value : null;
    }
}
