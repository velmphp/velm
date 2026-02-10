<?php

namespace Velm\Core\Auth;

use Illuminate\Auth\Access\Response;
use Velm\Core\Pipeline\ClassPipelineRuntime;

final class VelmPolicyDispatcher
{
    public static function authorize(
        string $ability,
        array $parameters
    ): bool|Response {
        [$user, $model] = $parameters;
        $logicalName = rtrim(class_basename(get_class($model)), 'Policy').'Policy';

        // Get the combined class
        $class = "\\Velm\\Policies\\$logicalName";
        if (! class_exists($class)) {
            return false;
        }

        return ClassPipelineRuntime::call(
            self: new $class,
            method: $ability,
            args: [$user, $model],
            injectSelf: false,
        );
    }
}
