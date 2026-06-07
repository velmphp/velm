<?php

declare(strict_types=1);

namespace Velm\Modules\Tests\Support;

use Velm\Environment;

final class ProbeSeeder
{
    /** @var list<string> */
    public static array $ran = [];

    public static function run(Environment $env): void
    {
        self::$ran[] = 'probe';
    }
}
