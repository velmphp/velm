<?php

declare(strict_types=1);

namespace Velm\Modules\Tests\Support;

final class ModuleRoots
{
    /**
     * Bundled modules plus skeleton demo addons (e.g. change_management).
     *
     * @return list<string>
     */
    public static function forTests(): array
    {
        return [
            dirname(__DIR__, 2).'/modules',
            dirname(__DIR__, 4).'/apps/skeleton/addons',
        ];
    }
}
