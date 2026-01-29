<?php

namespace Velm\Core\Modules\Concerns;

use Velm\Core\Compiler\GeneratedPaths;
use Velm\Core\Domain\Models\VelmModel;

trait RegistersDomainClasses
{
    final public static function getNamespaceFromPath(string $path): string
    {
        // extract relative path from module app path
        $appPath = rtrim(static::getAppPath(), DIRECTORY_SEPARATOR);
        $relativePath = str_replace($appPath.DIRECTORY_SEPARATOR, '', $path);
        // Remove .php
        $relativePath = str_replace('.php', '', $relativePath);
        // convert directory separators to namespace separators
        $namespace = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);
        // prepend module namespace
        $moduleNamespace = static::namespace();
        $fullNs = $moduleNamespace.'\\'.$namespace;

        return $fullNs;
    }

    final public static function discoverModels(): array
    {
        $modelsPath = static::getModelsPath();
        $models = [];
        if (is_dir($modelsPath)) {
            // Do a recursive scan and return all models which are descendants of Velm\Core\Domain\Models\VelmModel
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($modelsPath)
            );
            foreach ($iterator as $file) {
                /** @var \SplFileInfo $file */
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $class = static::getNamespaceFromPath($file->getRealPath());
                    if (class_exists($class) && is_subclass_of($class, VelmModel::class)) {
                        $models[] = $class;
                    }
                }
            }
        }

        return $models;
    }

    final public static function discoverPolicies(): array
    {
        $policiesPath = static::getPoliciesPath();

        return static::discoverClassesInPath($policiesPath);
    }

    final public static function discoverMigrations(): array
    {
        $migrationsPath = static::getMigrationsPath();
        $migrations = [];
        if (is_dir($migrationsPath)) {
            // Do a recursive scan and return all migration classes
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($migrationsPath)
            );
            // Sort files by name to ensure migrations are in order
            $files = [];
            foreach ($iterator as $file) {
                /** @var \SplFileInfo $file */
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $files[] = $file;
                }
            }
            usort($files, fn ($a, $b) => strcmp($a->getFilename(), $b->getFilename()));
            // Add the files to migrations
            foreach ($files as $file) {
                $migrations[] = $file->getRealPath();
            }
        }

        return $migrations;
    }

    final public static function discoverFactories(): array
    {
        $factoriesPath = static::getFactoriesPath();

        return static::discoverClassesInPath($factoriesPath);
    }

    final public static function discoverCommands(): array
    {
        $commandsPath = static::getCommandsPath();

        return static::discoverClassesInPath($commandsPath);
    }

    final protected static function discoverClassesInPath(string $path): array
    {
        $classes = [];
        if (is_dir($path)) {
            // Do a recursive scan and return all classes
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path)
            );
            foreach ($iterator as $file) {
                /** @var \SplFileInfo $file */
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $class = static::getNamespaceFromPath($file->getRealPath());
                    if (class_exists($class)) {
                        $classes[] = $class;
                    }
                }
            }
        }

        return $classes;
    }

    protected static function extraModels(): array
    {
        return [];
    }

    protected static function extraPolicies(): array
    {
        return [];
    }

    protected static function extraCommands(): array
    {
        return [];
    }

    abstract public static function packageName(): string;

    final public static function getModels(): array
    {
        return array_merge(
            static::discoverModels(),
            static::extraModels()
        );
    }

    final public static function getPolicies(): array
    {
        return array_merge(
            static::discoverPolicies(),
            static::extraPolicies()
        );
    }

    final public static function getCommands(): array
    {
        return array_merge(
            static::discoverCommands(),
            static::extraCommands()
        );
    }

    final public static function registerModels(): void
    {
        $models = static::getModels();
        // Sort models by priority
        usort($models, function (string|VelmModel $a, string|VelmModel $b) {
            $priorityA = $a::velm()->priority ?? 0;
            $priorityB = $b::velm()->priority ?? 0;

            return $priorityB <=> $priorityA;
        });
        // Register to model registry
        $modelRegistry = \Velm::registry()->models();
        $modelRegistry->register($models, static::packageName());
        static::loadModelProxies();
    }

    final public static function registerPolicies(): void
    {
        $policies = static::getPolicies();
        // TODO: Implement
    }

    final public static function registerCommands(): void
    {
        $commands = static::getCommands();
        // TODO: Implements
    }

    final public static function loadModelProxies(): void
    {
        $modelRegistry = \Velm::registry()->models();
        $proxies = $modelRegistry->proxies();
        // Load each proxy class
        foreach ($proxies as $proxyClass) {
            if (class_exists($proxyClass)) {
                continue;
            }
            // get path
            $path = GeneratedPaths::getModelPathFromClass($proxyClass);
            if (! file_exists($path)) {
                continue;
            }
            require_once $path;
        }
    }
}
