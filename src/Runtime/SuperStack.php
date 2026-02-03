<?php

namespace Velm\Core\Runtime;

class SuperStack
{
    protected static array $frames = [];

    public static function invoke(object $instance, array $stack, string $method, array $args)
    {
        self::$frames[] = [$instance, $stack, $method, 0];

        return self::next($args);
    }

    public static function next(array $args)
    {
        [$instance, $stack, $method, $i] = array_pop(self::$frames);

        // If the stack is empty, fall back to calling the Compiled class's method directly.
        if (empty($stack)) {
            // Call method on parent class
            return $instance::{$method}(...$args);
        }

        if (! isset($stack[$i])) {
            return null;
        }

        self::$frames[] = [$instance, $stack, $method, $i + 1];

        $fragment = new $stack[$i];

        return $fragment->{$method}(...$args);
    }
}
