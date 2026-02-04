<?php

namespace Velm\Core\Runtime;

class SuperStack
{
    protected static array $frames = [];

    public static function invoke(object $instance, array $stack, string $method, array $args)
    {
        self::$frames[] = [
            'instance' => $instance,
            'stack' => $stack,
            'method' => $method,
            'index' => 0,
        ];

        return self::next($args);
    }

    /**
     * @throws \ReflectionException
     */
    public static function next(array $args)
    {
        $frame = array_pop(self::$frames);

        if (! $frame) {
            return null;
        }

        $i = $frame['index'];
        $stack = $frame['stack'];

        // 1️⃣ End of pipeline → call parent method
        if (! isset($stack[$i])) {
            return $frame['instance']->callParent(
                $frame['method'],
                $args
            );
        }

        // 2️⃣ Continue pipeline
        self::$frames[] = [
            ...$frame,
            'index' => $i + 1,
        ];

        $fragmentClass = $stack[$i];
        $refMethod = new \ReflectionMethod($fragmentClass, $frame['method']);
        $refMethod->setAccessible(true);

        // 🔑 Create closure from fragment method and bind to runtime instance
        $closure = $refMethod->getClosure(new $fragmentClass);
        $closure = $closure->bindTo($frame['instance'], get_class($frame['instance']));

        return $closure(...$args);
    }
}
