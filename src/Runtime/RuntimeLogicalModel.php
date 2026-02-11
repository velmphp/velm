<?php

namespace Velm\Core\Runtime;

use Velm\Core\Models\LogicalModel;

class RuntimeLogicalModel extends LogicalModel
{
    public static string $logicalName = '';

    final public function getLogicalName(): string
    {
        if (empty(static::$logicalName)) {
            throw new \RuntimeException("Logical model '".static::class."' does not have a logical name defined.");
        }

        return velm_utils()->formatVelmName(static::$logicalName, 'Model');
    }
}
