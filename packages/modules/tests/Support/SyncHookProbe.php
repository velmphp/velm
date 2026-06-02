<?php

declare(strict_types=1);

namespace Velm\Modules\Tests\Support;

use Velm\Environment;

final class SyncHookProbe
{
    public static int $calls = 0;

    public static function sync(Environment $env): void
    {
        self::$calls++;
    }
}
