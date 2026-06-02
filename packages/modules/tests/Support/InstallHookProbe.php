<?php

declare(strict_types=1);

namespace Velm\Modules\Tests\Support;

use Velm\Environment;

final class InstallHookProbe
{
    public static int $calls = 0;

    public static function install(Environment $env): void
    {
        self::$calls++;
    }
}
