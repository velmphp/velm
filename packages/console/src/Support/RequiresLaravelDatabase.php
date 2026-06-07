<?php

declare(strict_types=1);

namespace Velm\Console\Support;

trait RequiresLaravelDatabase
{
    protected function laravelDatabaseAvailable(): bool
    {
        return class_exists(\Illuminate\Support\Facades\DB::class);
    }
}
