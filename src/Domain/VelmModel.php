<?php

namespace Velm\Core\Domain;

use Velm\Core\Eloquent\PipelineModel;

abstract class VelmModel extends PipelineModel
{
    public static int $velm_priority = 0;

    public static ?string $velm_name = null;
    // Marker class for domain model compilation

    public function getLogicalName(): string
    {
        $name = static::$velm_name ?? class_basename(static::class);

        return velm_utils()->formatVelmName($name, 'Model');
    }
}
