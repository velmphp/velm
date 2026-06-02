<?php

declare(strict_types=1);

namespace Velm\Framework\Facades;

use Illuminate\Support\Facades\Facade;

/** @method static \Velm\Environment environment() */
final class Velm extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'velm';
    }
}
