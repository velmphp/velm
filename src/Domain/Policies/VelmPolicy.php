<?php

namespace Velm\Core\Domain\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Velm\Core\Compiler\VelmPolicyCompiler;
use Velm\Core\Concerns\BelongsToVelmModule;
use Velm\Core\Contracts\VelmClassContract;
use Velm\Core\Contracts\VelmCompilable;
use Velm\Core\Contracts\VelmCompilerContract;

/**
 * @method mixed super(...$args) Calls next pipeline step. This is not a real method, but is handled dynamically. Use within pipeline methods to call the next step in the inheritance chain.
 */
abstract class VelmPolicy implements VelmClassContract, VelmCompilable
{
    use BelongsToVelmModule, HandlesAuthorization;

    public static function getCompiler(): VelmCompilerContract
    {
        return new VelmPolicyCompiler;
    }
}
