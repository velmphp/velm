<?php

declare(strict_types=1);

namespace Velm\Modules\Support;

final class ModuleNaming
{
    public static function studlyModuleName(string $moduleName): string
    {
        return str_replace('_', '', ucwords($moduleName, '_'));
    }

    public static function studlyToSnake(string $studly): string
    {
        $snake = preg_replace('/([A-Z])/', '_$1', lcfirst($studly));

        return strtolower((string) $snake);
    }

    public static function isBundledModulePath(string $modulePath): bool
    {
        $normalized = str_replace('\\', '/', $modulePath);

        return str_contains($normalized, '/modules/modules/');
    }

    public static function modelsNamespace(string $modulePath, string $moduleName): string
    {
        $studly = self::studlyModuleName($moduleName);

        if (self::isBundledModulePath($modulePath)) {
            return "Velm\\Modules\\{$studly}\\Models";
        }

        return "Addons\\{$studly}\\Models";
    }

    public static function classNameFromStem(string $stem): string
    {
        return str_replace('_', '', ucwords($stem, '_'));
    }

    public static function classStemFromShortName(string $shortClassName): string
    {
        $snake = preg_replace('/([A-Z])/', '_$1', lcfirst($shortClassName));

        return strtolower(ltrim((string) $snake, '_'));
    }
}
