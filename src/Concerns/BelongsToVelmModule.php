<?php

namespace Velm\Core\Concerns;

use Velm\Core\Contracts\VelmCompilable;
use Velm\Core\Domain\DomainDescriptor;
use Velm\Core\Modules\ModuleDescriptor;

trait BelongsToVelmModule
{
    protected static string $_velm_name = '';

    protected static ?string $_velm_proxy_class_candidate = null;

    /**
     * Describe the model for Velm so that it can be registered properly.
     *
     * @throws \ReflectionException
     */
    final public static function velm(): DomainDescriptor
    {
        // Get the base name o the called class
        $calledClass = get_called_class();
        // If the $_velm_name is set, use it
        $name = static::$_velm_name;
        if (empty($name)) {
            $name = class_basename($calledClass);
        }
        $module = static::module();

        return DomainDescriptor::make(name: $name, module: $module, proxyCandidateClass: static::proxyCandidateClass());
    }

    /**
     * Get the Module to which this class belongs.
     */
    final public static function module(): ?ModuleDescriptor
    {
        $calledClass = get_called_class();

        return \Velm::registry()->modules()->findForClass($calledClass);
    }

    /**
     * @throws \ReflectionException
     */
    final public static function proxyCandidateClass(): ?string
    {
        $calledClass = get_called_class();
        $ns = new \ReflectionClass($calledClass)->getName();
        $module = static::module();
        if ($module) {
            $relativeNs = str($ns)->replace($module->entryPoint::getNamespaceFromPath($module->entryPoint::getModelsPath()), '')->rtrim('\\')->toString();

            return rtrim(config('velm.compiler.generated_namespaces.Models'), '\\').'\\'.ltrim($relativeNs, '\\');
        }

        return null;
    }

    /**
     * Get the initial definition class for this model.
     *
     * @return class-string<VelmCompilable> The initial definition class for this model
     */
    final public static function initialDefinition(): string
    {
        $calledClass = get_called_class();
        $logicalName = $calledClass::velm()->name;
        $definitions = \Velm::registry()->models()->definitions($logicalName);
        if (! empty($definitions)) {
            return $definitions[0];
        }
        throw new \RuntimeException("No definitions found for model {$logicalName}.");
    }

    // Accessors
    final public static function getName(): string
    {
        return static::velm()->name;
    }
}
