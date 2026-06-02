<?php

declare(strict_types=1);

namespace Velm\Console\Support;

final class ModuleRoots
{
    /**
     * @return list<string>
     */
    public static function resolve(): array
    {
        if (function_exists('app')) {
            $app = app();

            if ($app->bound('config')) {
                /** @var list<string>|null $paths */
                $paths = $app->make('config')->get('velm.addon_paths');

                if (is_array($paths) && $paths !== []) {
                    return $paths;
                }
            }
        }

        $bundled = dirname(__DIR__, 3).'/modules/modules';

        return array_values(array_filter([
            is_dir($bundled) ? $bundled : null,
        ]));
    }

    /**
     * @return list<string>
     */
    public static function bootstrapModules(): array
    {
        if (function_exists('app')) {
            $app = app();

            if ($app->bound('config')) {
                /** @var list<string>|null $modules */
                $modules = $app->make('config')->get('velm.bootstrap_modules');

                if (is_array($modules) && $modules !== []) {
                    return $modules;
                }
            }
        }

        return ['base', 'admin'];
    }
}
