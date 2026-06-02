<?php

declare(strict_types=1);

namespace Velm\Framework\Console;

use Illuminate\Console\Command;
use Velm\Console\Support\ModuleRoots;
use Velm\Modules\ModuleInstaller;

final class ModuleListCommand extends Command
{
    protected $signature = 'velm:module:list';

    protected $description = 'List discovered Velm modules and install state';

    public function handle(ModuleInstaller $installer): int
    {
        $rows = $installer->catalog(ModuleRoots::resolve());

        $this->table(
            ['Module', 'State', 'Version', 'Depends', 'Summary'],
            array_map(
                static fn (array $row): array => [
                    $row['name'],
                    $row['state'],
                    $row['version'],
                    $row['depends'],
                    $row['summary'],
                ],
                $rows,
            ),
        );

        return self::SUCCESS;
    }
}
