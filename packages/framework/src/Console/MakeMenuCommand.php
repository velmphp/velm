<?php

declare(strict_types=1);

namespace Velm\Framework\Console;

use Illuminate\Console\Command;
use Velm\Console\Scaffold\MenuScaffolder;
use Velm\Console\Scaffold\ModulePathResolver;

final class MakeMenuCommand extends Command
{
    protected $signature = 'velm:make:menu
                            {--view= : List view name (e.g. product.list)}
                            {--module= : Owning module (inferred from cwd when omitted)}
                            {--path= : Addon root to search}
                            {--group=main : Sidebar group id}
                            {--group-label= : Group label (default: module title)}
                            {--item= : Menu item id (default: {group}.{view-stem})}
                            {--item-label= : Menu item label}
                            {--append : Add an item to an existing views/menu.php}
                            {--force : Overwrite views/menu.php}';

    protected $description = 'Scaffold sidebar menu entries for a list view';

    public function handle(MenuScaffolder $scaffolder): int
    {
        $view = $this->option('view');

        if (! is_string($view) || trim($view) === '') {
            $this->components->error('--view= is required (e.g. --view=product.list).');

            return self::FAILURE;
        }

        $moduleName = $this->option('module');

        if (! is_string($moduleName) || $moduleName === '') {
            $moduleName = ModulePathResolver::inferModuleFromCwd();
        }

        if (! is_string($moduleName) || $moduleName === '') {
            $this->components->error('Pass --module=<name> or run from inside <addon-root>/<module>/.');

            return self::FAILURE;
        }

        $moduleName = strtolower($moduleName);
        $addonRoot = $this->option('path');
        $addonRoot = is_string($addonRoot) && $addonRoot !== '' ? $addonRoot : null;
        $group = (string) ($this->option('group') ?: 'main');
        $groupLabel = $this->option('group-label');
        $item = $this->option('item');
        $itemLabel = $this->option('item-label');

        try {
            $modulePath = ModulePathResolver::findModulePath($moduleName, $addonRoot);
            $result = $scaffolder->scaffold(
                $moduleName,
                $modulePath,
                $view,
                $group,
                is_string($groupLabel) && $groupLabel !== '' ? $groupLabel : null,
                is_string($item) && $item !== '' ? $item : null,
                is_string($itemLabel) && $itemLabel !== '' ? $itemLabel : null,
                60,
                (bool) $this->option('append'),
                (bool) $this->option('force'),
            );
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Updated {$result['path']}");
        $this->line("  View: {$result['view']}");
        $this->line("  Item: {$result['item']}");
        $this->line("  php artisan velm:module:sync --module={$moduleName}");

        return self::SUCCESS;
    }
}
