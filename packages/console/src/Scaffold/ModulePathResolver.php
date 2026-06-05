<?php

declare(strict_types=1);

namespace Velm\Console\Scaffold;

use Velm\Console\Support\ModuleRoots;
use Velm\Modules\Support\ModuleNaming;

final class ModulePathResolver
{
    private const NAME_PATTERN = '/^[a-z][a-z0-9_]{0,49}$/';

    public static function resolveAddonRoot(?string $explicit): string
    {
        if (is_string($explicit) && $explicit !== '') {
            return $explicit;
        }

        if (function_exists('base_path')) {
            $appAddons = base_path('addons');

            if (is_string($appAddons) && $appAddons !== '') {
                return $appAddons;
            }
        }

        foreach (ModuleRoots::resolve() as $path) {
            if (! str_contains($path, DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR)) {
                return $path;
            }
        }

        return 'addons';
    }

    /**
     * @return list<string>
     */
    public static function moduleSearchRoots(?string $addonRoot = null): array
    {
        if (is_string($addonRoot) && $addonRoot !== '') {
            return [$addonRoot];
        }

        return ModuleRoots::resolve();
    }

    public static function findModulePath(string $moduleName, ?string $addonRoot = null): string
    {
        if (! preg_match(self::NAME_PATTERN, $moduleName)) {
            throw new \InvalidArgumentException(
                'Module name must be snake_case: start with a letter, then letters, digits, or underscores (max 50 chars).',
            );
        }

        foreach (self::moduleSearchRoots($addonRoot) as $root) {
            $candidate = rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$moduleName;

            if (is_file($candidate.'/__velm__.php')) {
                return $candidate;
            }
        }

        $searched = implode(', ', self::moduleSearchRoots($addonRoot));

        throw new \RuntimeException("Module {$moduleName} not found under: {$searched}");
    }

    public static function inferModuleFromCwd(): ?string
    {
        $cwd = getcwd();

        if ($cwd === false) {
            return null;
        }

        $cwd = realpath($cwd) ?: $cwd;

        foreach (ModuleRoots::resolve() as $root) {
            $rootReal = realpath($root) ?: $root;

            if (! str_starts_with($cwd, $rootReal.DIRECTORY_SEPARATOR)) {
                continue;
            }

            $relative = substr($cwd, strlen($rootReal) + 1);
            $first = explode(DIRECTORY_SEPARATOR, $relative)[0] ?? '';

            if ($first !== '' && preg_match(self::NAME_PATTERN, $first)) {
                return $first;
            }
        }

        return null;
    }

    public static function studlyModuleName(string $moduleName): string
    {
        return ModuleNaming::studlyModuleName($moduleName);
    }

    public static function isBundledModulePath(string $modulePath): bool
    {
        return ModuleNaming::isBundledModulePath($modulePath);
    }

    public static function modelsNamespace(string $modulePath, string $moduleName): string
    {
        return ModuleNaming::modelsNamespace($modulePath, $moduleName);
    }
}
