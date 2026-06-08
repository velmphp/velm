<?php

declare(strict_types=1);

namespace Velm\Support;

use Velm\Environment;
use Velm\Recordset\Recordset;

/**
 * Optional listener for ORM create/write/unlink (used by audit and similar modules).
 */
final class RecordChangeNotifier
{
    /** @var callable(Environment, Recordset, array<string, mixed>, string, array<string, mixed>): void|null */
    private static $listener = null;

    /**
     * @param  callable(Environment, Recordset, array<string, mixed>, string, array<string, mixed>): void  $listener
     */
    public static function listen(callable $listener): void
    {
        self::$listener = $listener;
    }

    public static function reset(): void
    {
        self::$listener = null;
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  array<string, mixed>  $context
     */
    public static function notify(
        Environment $env,
        Recordset $recordset,
        array $values,
        string $operation,
        array $context = [],
    ): void {
        if (self::$listener === null) {
            return;
        }

        (self::$listener)($env, $recordset, $values, $operation, $context);
    }
}
