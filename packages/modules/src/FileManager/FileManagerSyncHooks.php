<?php

declare(strict_types=1);

namespace Velm\Modules\FileManager;

use Velm\Environment;

final class FileManagerSyncHooks
{
    public static function sync(Environment $env): void
    {
        FileManagerCompanyScope::backfillOrphans($env);
    }
}
