<?php

declare(strict_types=1);

namespace Velm\Modules\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class BundledModulesShipTest extends TestCase
{
    public function test_bundled_modules_directory_ships_with_package(): void
    {
        $packageRoot = dirname(__DIR__, 2);
        $modulesRoot = $packageRoot.'/modules';

        self::assertDirectoryExists($modulesRoot);

        foreach (['base', 'admin', 'partners'] as $module) {
            self::assertFileExists(
                $modulesRoot.'/'.$module.'/__velm__.php',
                "Bundled module [{$module}] must ship in velmphp/modules dist",
            );
        }
    }
}
