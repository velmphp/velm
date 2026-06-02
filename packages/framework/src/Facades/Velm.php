<?php

declare(strict_types=1);

namespace Velm\Framework\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Velm\Environment environment()
 * @method static void installBootstrap(list<string> $modules = [])
 * @method static void install(string $moduleName)
 * @method static list<string> addonPaths()
 */
final class Velm extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'velm';
    }
}
