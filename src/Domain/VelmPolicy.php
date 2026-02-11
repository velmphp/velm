<?php

namespace Velm\Core\Domain;

use Velm\Core\Pipeline\Contracts\Pipelinable;

abstract class VelmPolicy implements Pipelinable
{
    protected static ?string $velm_name = null;

    public static int $velm_priority = 0;

    public function getLogicalName(): string
    {
        $name = static::$velm_name ?? class_basename(get_called_class());

        return velm_utils()->formatVelmName($name, 'Policy');
    }
}
