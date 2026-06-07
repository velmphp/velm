<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Illuminate\Database\Connection as IlluminateConnection;
use Illuminate\Database\Schema\Builder;
use Velm\Database\Connection;
use Velm\Database\PdoConnection;

/**
 * SQLite-backed connection that reports a MySQL driver for schema differ tests.
 */
final class MysqlSchemaTestConnection implements Connection
{
    private readonly PdoConnection $inner;

    private readonly IlluminateConnection $illuminate;

    public function __construct()
    {
        $this->inner = PdoConnection::sqliteMemory();
        $this->illuminate = $this->inner->illuminateConnection();

        $builder = $this->illuminate->getSchemaBuilder();
        $builder->create('res_partner', static function ($table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedBigInteger('country_id')->nullable();
        });
    }

    public function execute(string $sql, array $params = []): void
    {
        $this->inner->execute($sql, $params);
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        if (str_contains($sql, 'information_schema.columns')) {
            $column = (string) ($params[2] ?? '');

            return match ($column) {
                'name' => [['is_nullable' => 'YES']],
                'active' => [['IS_NULLABLE' => 'NO']],
                default => [],
            };
        }

        return $this->inner->fetchAll($sql, $params);
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        return $this->inner->fetchOne($sql, $params);
    }

    public function lastInsertId(): int
    {
        return $this->inner->lastInsertId();
    }

    public function illuminateConnection(): IlluminateConnection
    {
        $inner = $this->illuminate;
        $mock = \Mockery::mock(IlluminateConnection::class);
        $mock->shouldReceive('getDriverName')->andReturn('mysql');
        $mock->shouldReceive('getDatabaseName')->andReturn('velm_test');
        $mock->shouldReceive('getSchemaBuilder')->andReturn($inner->getSchemaBuilder());
        $mock->shouldReceive('getQueryGrammar')->andReturn($inner->getQueryGrammar());

        return $mock;
    }

    public function driver(): string
    {
        return 'mysql';
    }
}
