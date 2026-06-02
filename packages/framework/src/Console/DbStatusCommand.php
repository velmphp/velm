<?php

declare(strict_types=1);

namespace Velm\Framework\Console;

use Illuminate\Console\Command;
use Velm\Console\Support\ModuleRoots;
use Velm\Modules\ModuleInstaller;

final class DbStatusCommand extends Command
{
    protected $signature = 'velm:db:status';

    protected $description = 'Show installed module versions vs manifest';

    public function handle(ModuleInstaller $installer): int
    {
        $rows = $installer->schemaStatus(ModuleRoots::resolve());

        if ($rows === []) {
            $this->components->warn('No installed modules.');

            return self::SUCCESS;
        }

        $this->table(
            ['Module', 'Installed', 'Manifest', 'Status'],
            array_map(
                static fn (array $row): array => [
                    $row['name'],
                    $row['installed'] ?? '—',
                    $row['manifest'],
                    $row['status'],
                ],
                $rows,
            ),
        );

        return self::SUCCESS;
    }
}
