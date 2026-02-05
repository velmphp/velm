<?php

namespace Velm\Core\Pipeline;

final class PipelineContext
{
    private static array $stack = [];

    public static function push(mixed $super): void
    {
        self::$stack[] = $super;
    }

    public static function pop(): void
    {
        array_pop(self::$stack);
    }

    public static function super(): SuperProxy
    {
        if (empty(self::$stack)) {
            throw new \RuntimeException('super() called outside pipeline');
        }

        return self::$stack[array_key_last(self::$stack)];
    }
}
