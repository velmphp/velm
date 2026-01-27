<?php

namespace Velm\Core\Domain\Models;

use Velm\Core\Compiler\GeneratedPaths;
use Velm\Core\Modules\ModuleDescriptor;

use function Symfony\Component\String\s;

final readonly class ModelDescriptor
{
    public function __construct(
        public string $name,
        public ModuleDescriptor $module,
        public string $proxyCandidateClass,
    ) {}

    // Factory method to create a ModelDescriptor

    /**
     * @throws \ReflectionException
     */
    public static function make(?string $name = null, ?ModuleDescriptor $module = null, ?string $proxyCandidateClass = null): ModelDescriptor
    {
        if (empty($name)) {
            // Get the name of the class from where this was called.
            $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];
            $callerClass = $caller['class'] ?? null;
            // if the caller is a static method, use the class name
            if (isset($callerClass)) {
                $name = basename(str_replace(['\\', '.php'], ['/', ''], $callerClass));
            } else {
                throw new \InvalidArgumentException('Cannot determine the class name for '.$callerClass.'.');
            }
        }
        if (empty($module)) {
            // Try to get the module from the caller's class namespace
            $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];
            $callerClass = $caller['class'] ?? null;
            if (isset($callerClass)) {
                $module = $callerClass::module() ?? null;
            }
            if (empty($module)) {
                throw new \InvalidArgumentException('Cannot determine the module for '.$callerClass.'.');
            }
        }

        if (empty($proxyCandidateClass)) {
            $modelsPath = $module->entryPoint::getModelsPath();
            $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];
            $callerClass = $caller['class'] ?? null;
            if (isset($callerClass)) {
                $path = new \ReflectionClass($callerClass)->getFileName();
                $relativePath = str($path)->replace($modelsPath, '')->ltrim('/\\')->toString();
                $relativeNamespace = str($relativePath)->replace(DIRECTORY_SEPARATOR, '\\')->replace('.php', '')->toString();
                $proxyCandidateClass = config('velm.compiler.generated_namespaces.Models', 'Velm\\Models').'\\'.$relativeNamespace;
            }
        }
        if (empty($proxyCandidateClass)) {
            throw new \InvalidArgumentException('Cannot determine the proxy class candidate for '.$name.'.');
        }

        return new ModelDescriptor(
            name: $name,
            module: $module,
            proxyCandidateClass: $proxyCandidateClass
        );
    }

    final public function getProxyCandidatePath(): string
    {
        $relativePath = str($this->getRelativeNamespace())->replace('\\', DIRECTORY_SEPARATOR)
            ->toString();

        return GeneratedPaths::models($relativePath.'.php');
    }

    final public function getRelativeNamespace(): string
    {
        return str($this->proxyCandidateClass)
            ->replace(config('velm.compiler.generated_namespaces.Models', 'Velm\\Models').'\\', '')
            ->toString();
    }
}
