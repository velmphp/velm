<?php

declare(strict_types=1);

namespace Velm\Database;

interface Connection
{
    /**
     * @param  array<int|string, mixed>  $params
     */
    public function execute(string $sql, array $params = []): void;

    /**
     * @param  array<int|string, mixed>  $params
     * @return list<array<string, mixed>>
     */
    public function fetchAll(string $sql, array $params = []): array;

    /**
     * @param  array<int|string, mixed>  $params
     * @return array<string, mixed>|null
     */
    public function fetchOne(string $sql, array $params = []): ?array;

    public function lastInsertId(): int;
}
