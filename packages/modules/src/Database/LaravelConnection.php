<?php

declare(strict_types=1);

namespace Velm\Modules\Database;

use Illuminate\Database\Connection as IlluminateConnection;
use Velm\Database\Connection;

final class LaravelConnection implements Connection
{
    public function __construct(
        private readonly IlluminateConnection $connection,
    ) {}

    public function execute(string $sql, array $params = []): void
    {
        $this->connection->statement($sql, $params);
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return array_map(
            static fn (object|array $row): array => (array) $row,
            $this->connection->select($sql, $params),
        );
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        $row = $this->connection->selectOne($sql, $params);

        return $row === null ? null : (array) $row;
    }

    public function lastInsertId(): int
    {
        return (int) $this->connection->getPdo()->lastInsertId();
    }

    public function driver(): string
    {
        return $this->connection->getDriverName();
    }

    public function illuminateConnection(): IlluminateConnection
    {
        return $this->connection;
    }
}
