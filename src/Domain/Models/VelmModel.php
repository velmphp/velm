<?php

namespace Velm\Core\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Velm\Core\Compiler\Concerns\IsCompilable;
use Velm\Core\Compiler\VelmModelCompiler;
use Velm\Core\Concerns\BelongsToVelmModule;
use Velm\Core\Contracts\VelmClassContract;
use Velm\Core\Contracts\VelmCompilable;
use Velm\Core\Contracts\VelmCompilerContract;
use Velm\Core\Domain\Contracts\VelmModelContract;
use Velm\Core\Metadata\Access\VelmAccess;
use Velm\Core\Metadata\Contracts\ProvidesVelmMetadataFragment;
use Velm\Core\Metadata\Lifecycle\VelmLifecycle;

abstract class VelmModel extends Model implements ProvidesVelmMetadataFragment, VelmClassContract, VelmCompilable, VelmModelContract
{
    use BelongsToVelmModule, IsCompilable;

    public static function getCompiler(): VelmCompilerContract
    {
        return new VelmModelCompiler;
    }

    public function fields(): array
    {
        return [];
    }

    public function relations(): array
    {
        return [];
    }

    public function definedActions(): array
    {
        return [];
    }

    public function access(): ?VelmAccess
    {
        return new VelmAccess;
    }

    public function lifecycle(): ?VelmLifecycle
    {
        return null;
    }

    public function presentation(): array
    {
        return [];
    }
}
