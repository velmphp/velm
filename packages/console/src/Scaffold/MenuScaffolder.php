<?php

declare(strict_types=1);

namespace Velm\Console\Scaffold;

final class MenuScaffolder
{
    /**
     * @return array{path: string, view: string, item: string}
     */
    public function scaffold(
        string $moduleName,
        string $modulePath,
        string $viewName,
        string $group = 'main',
        ?string $groupLabel = null,
        ?string $itemName = null,
        ?string $itemLabel = null,
        int $groupSequence = 60,
        bool $append = false,
        bool $force = false,
    ): array {
        $viewName = strtolower(trim($viewName));

        if ($viewName === '') {
            throw new \InvalidArgumentException('--view= is required (e.g. product.list).');
        }

        $viewStem = explode('.', $viewName);
        $viewStem = end($viewStem) ?: $viewName;

        $groupLabel ??= str_replace('_', ' ', ucwords($moduleName, '_'));
        $itemName ??= "{$group}.{$viewStem}";
        $itemLabel ??= str_replace('_', ' ', ucwords($viewStem, '_'));

        $target = $modulePath.'/views/menu.php';
        $itemBlock = $this->appendItemBlock($moduleName, $group, $itemName, $itemLabel, $viewName);

        if (is_file($target)) {
            if ($append) {
                $text = file_get_contents($target);

                if ($text === false) {
                    throw new \RuntimeException("Could not read {$target}.");
                }

                if (str_contains($text, "->view('{$viewName}')")) {
                    return ['path' => $target, 'view' => $viewName, 'item' => $itemName];
                }

                if (! preg_match('/->menus\s*\(/', $text)) {
                    throw new \RuntimeException("{$target} has no ->menus() block to append to.");
                }

                $updated = preg_replace(
                    '/(\s*\)\s*;\s*)$/',
                    ",\n{$itemBlock}\n    );",
                    rtrim($text)."\n",
                    1,
                );

                if (! is_string($updated)) {
                    throw new \RuntimeException("Could not append menu item to {$target}.");
                }

                file_put_contents($target, $updated);

                return ['path' => $target, 'view' => $viewName, 'item' => $itemName];
            }

            if (! $force) {
                throw new \RuntimeException(
                    "{$target} already exists — pass --append to add an item or --force to overwrite.",
                );
            }
        }

        if (! is_dir($modulePath.'/views') && ! mkdir($modulePath.'/views', 0775, true) && ! is_dir($modulePath.'/views')) {
            throw new \RuntimeException("Could not create views directory under {$modulePath}.");
        }

        file_put_contents(
            $target,
            $this->menuContents($moduleName, $group, $groupLabel, $itemName, $itemLabel, $viewName, $groupSequence),
        );

        ManifestPatcher::appendData($modulePath.'/__velm__.php', 'views/menu.php');

        return ['path' => $target, 'view' => $viewName, 'item' => $itemName];
    }

    private function menuContents(
        string $moduleName,
        string $group,
        string $groupLabel,
        string $itemName,
        string $itemLabel,
        string $viewName,
        int $groupSequence,
    ): string {
        return <<<PHP
<?php

declare(strict_types=1);

use Velm\Views\Authoring\Menus;
use Velm\Views\Data\ViewsData;

\$m = new Menus('{$moduleName}');

return ViewsData::make()
    ->menus(
        \$m->group('{$group}', '{$groupLabel}')
            ->sequence({$groupSequence})
            ->children(
                \$m->item('{$itemName}', '{$itemLabel}')
                    ->view('{$viewName}')
                    ->sequence(10),
            ),
    );

PHP;
    }

    private function appendItemBlock(
        string $moduleName,
        string $group,
        string $itemName,
        string $itemLabel,
        string $viewName,
    ): string {
        return <<<PHP
        \$m->item('{$itemName}', '{$itemLabel}')
            ->parentRef('{$moduleName}.{$group}')
            ->view('{$viewName}')
            ->sequence(10)
PHP;
    }
}
