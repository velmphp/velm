<?php

namespace Velm\Core\Registry;

use Velm\Core\Domain\VelmService;
use Velm\Core\Runtime\ServiceManager;

class ServiceRegistry
{
    protected array $services = [];

    private array $map = [];

    public function register(string $package, VelmService $instance): void
    {
        // The point is to have an array of package => []class, so we can easily resolve services by their logical name.
        // Do not duplicate classes if they already exist for a logical name.
        if (! isset($this->services[$package])) {
            $this->services[$package] = [];
        }
        $this->services[$package][$instance->getLogicalName()] = $instance;
    }

    public function all(): array
    {
        return $this->services;
    }

    public function servicesFor(string $package)
    {
        return $this->services[$package] ?? [];
    }

    public function servicesMap(): array
    {
        if (empty($this->map)) {
            foreach ($this->services as $package => $services) {
                foreach ($services as $logicalName => $instance) {
                    if (! isset($this->map[$logicalName])) {
                        $this->map[$logicalName] = [];
                    }
                    $this->map[$logicalName][] = $instance;
                }
            }
        }

        return $this->map;
    }

    public function extensions(string $logicalName): array
    {
        $logicalName = velm_utils()->formatVelmName($logicalName, 'Service');
        $map = $this->servicesMap();

        return $map[$logicalName] ?? [];
    }

    public function discoverForPackage(string $package, bool $autoRegister = false): array
    {
        $module = velm()->registry()->modules()->findOrFail($package);
        // discover all classes in the namespace that extend VelmService
        $path = $module->entryPoint::getAppPath();
        $classes = [];
        // Scan recursively for class files in the app path
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                // get fqcn from file path and namespace
                $class = $module->entryPoint::getNamespaceFromPath($file->getRealPath());
                if (class_exists($class) && is_subclass_of($class, \Velm\Core\Domain\VelmService::class)) {
                    // Get the logical name from the service class
                    $classes[] = $class;
                }
            }
        }
        if ($autoRegister) {
            foreach ($classes as $class) {
                $instance = new $class;
                $this->register($package, $instance);
            }
        }

        return $classes;
    }

    public function bootstrap(): void
    {
        $services = $this->servicesMap();
        foreach ($services as $logicalName => $instances) {
            // Add to pipeline
            foreach ($instances as $instance) {
                velm()->registry()->pipeline()::register($instance, $logicalName);
                velm()->registry()->pipeline()::registerStatic(get_class($instance), $logicalName);
            }
            (new ServiceManager)->instance($logicalName);
        }
    }
}
