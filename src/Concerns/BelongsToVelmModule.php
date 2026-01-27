<?php

namespace Velm\Core\Concerns;

use Velm\Core\Domain\Models\ModelDescriptor;
use Velm\Core\Modules\ModuleDescriptor;

trait BelongsToVelmModule
{
    protected static string $_velm_name = '';

    protected static ?string $_velm_proxy_class_candidate = null;

    /**
     * Describe the model for Velm so that it can be registered properly.
     */
    final public static function velm(): ModelDescriptor
    {
        // Get the base name o the called class
        $calledClass = get_called_class();
        // If the $_velm_name is set, use it
        $name = static::$_velm_name;
        if (empty($name)) {
            $name = class_basename($calledClass);
        }
        $module = static::module();

        return ModelDescriptor::make(name: $name, module: $module, proxyCandidateClass: $calledClass::proxyCandidateClass());
    }

    /**
     * Get the Module to which this class belongs.
     */
    final public static function module(): ?ModuleDescriptor
    {
        $calledClass = get_called_class();

        return \Velm::registry()->modules()::findForClass($calledClass);
    }

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
}
