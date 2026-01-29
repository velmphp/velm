<?php

namespace Velm\Core\Compiler;

class GeneratedPaths
{
    public static function base(string $path = ''): string
    {
        $path = str($path)->ltrim('/\\')->prepend('framework/cache/velm/')->rtrim('/\\')->toString();

        return storage_path($path);
    }

    public static function models(string $path = ''): string
    {
        $path = str($path)->ltrim('/\\')->prepend('framework/cache/velm/Models/')->rtrim('/\\')->toString();

        return storage_path($path);
    }

    public static function policies(string $path = ''): string
    {
        $path = str($path)->ltrim('/\\')->prepend('framework/cache/velm/Policies/')->rtrim('/\\')->toString();

        return storage_path($path);
    }

    public static function getModelPathFromClass(string $class): string
    {
        $relativePath = str($class)
            ->replace(config('velm.compiler.generated_namespaces.Models', 'Velm\\Models').'\\', '')
            ->replace('\\', DIRECTORY_SEPARATOR)
            ->append('.php')
            ->toString();

        return self::models($relativePath);
    }
}
