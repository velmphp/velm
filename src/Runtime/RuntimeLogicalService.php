<?php

namespace Velm\Core\Runtime;

use Velm\Core\Pipeline\Contracts\Pipelinable;

class RuntimeLogicalService implements Pipelinable
{
    public static string $logicalName = '';

    public function getLogicalName(): string
    {
        if (empty(static::$logicalName)) {
            throw new \RuntimeException("Logical service '".static::class."' does not have a logical name defined.");
        }

        return velm_utils()->formatVelmName(static::$logicalName, 'Service');
    }
}
