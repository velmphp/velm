<?php

namespace Velm\Core\Compiler\Concerns;

use Velm\Core\Contracts\VelmCompilable;

/**
 * @method mixed super(...$args) Calls next pipeline step. This is not a real method, but is handled dynamically. Use within pipeline methods to call the next step in the inheritance chain.
 */
trait IsCompilable
{
    public static function compile(bool $lazy = false): void
    {
        $called = get_called_class();
        /**
         * @var class-string<VelmCompilable> $base
         */
        $base = $called::initialDefinition();
        $compiler = $base::getCompiler();
        velm_utils()->consoleLog("Compiling Velm Compilable: {$base}");
        $compiler->compileSingle($base, lazy: $lazy);
    }
}
