<?php

declare(strict_types=1);

namespace Velm\Database;

final class SqlQuote
{
    public static function identifier(Connection $connection, string $name): string
    {
        return $connection->illuminateConnection()->getQueryGrammar()->wrap($name);
    }
}
