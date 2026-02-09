<?php

namespace Velm\Core\Domain;

use Velm\Core\Pipeline\Contracts\Pipelinable;

abstract class BasePolicy implements Pipelinable
{
    public function getLogicalName(): string
    {
        $called = get_called_class();

        return class_basename($called);
    }
}
