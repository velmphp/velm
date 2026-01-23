<?php

namespace Velm\Core\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Velm\Core\Velm
 */
class Velm extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'velm';
    }
}
