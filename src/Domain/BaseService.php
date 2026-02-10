<?php

namespace Velm\Core\Domain;

use Velm\Core\Pipeline\Contracts\Pipelinable;

abstract class BaseService implements Pipelinable
{
    public function getLogicalName(): string
    {
        $called = get_called_class();

        return str(class_basename($called))->rtrim('Service')->append('Service')->toString();
    }
}
