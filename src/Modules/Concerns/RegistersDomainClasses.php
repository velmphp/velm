<?php

namespace Velm\Core\Modules\Concerns;

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

    abstract public static function packageName(): string;

    final public static function registerModels(): void
    {
        velm()->registry()->models()->discoverForPackage(static::packageName(), autoRegister: true);
    }

    final public static function registerServices(): void
    {
        velm()->registry()->services()->discoverForPackage(static::packageName(), autoRegister: true);
    }

    final public static function registerCommands(): void
    {
        // TODO: Implements
    }
}
