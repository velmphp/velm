<?php

declare(strict_types=1);

namespace Velm\Modules\Seeding;

use Velm\Environment;

interface ModuleSeeder
{
    public static function run(Environment $env): void;
}

