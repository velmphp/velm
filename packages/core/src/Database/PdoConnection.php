<?php

declare(strict_types=1);

namespace Velm\Database;

use PDO;
use PDOStatement;

final class PdoConnection implements Connection
{
    public function __construct(
        private readonly PDO $pdo,
    ) {}

    public static function sqliteMemory(): self
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return new self($pdo);
    }

    public function execute(string $sql, array $params = []): void
    {
        $this->statement($sql, $params)->execute();
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        $statement = $this->statement($sql, $params);
        $statement->execute();

        /** @var list<array<string, mixed>> $rows */
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        $statement = $this->statement($sql, $params);
        $statement->execute();
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    public function lastInsertId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }

    public function driver(): string
    {
        $name = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

        return match ($name) {
            'mysql' => 'mysql',
            'pgsql' => 'pgsql',
            default => 'sqlite',
        };
    }

    /**
     * @param  array<int|string, mixed>  $params
     */
    private function statement(string $sql, array $params): PDOStatement
    {
        $statement = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $statement->bindValue(is_int($key) ? $key + 1 : $key, $value);
        }

        return $statement;
    }
}
