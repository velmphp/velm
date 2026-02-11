<?php

namespace Velm\Core\Domain;

use Velm\Core\Pipeline\Contracts\Pipelinable;

abstract class VelmService implements Pipelinable
{
    protected static ?string $velm_name = null;

    public function getLogicalName(): string
    {
        $name = static::$velm_name ?? class_basename(get_called_class());

        return velm_utils()->formatVelmName($name, 'Service');
    }

    public static function instance(): static
    {
        return new static;
    }
}
