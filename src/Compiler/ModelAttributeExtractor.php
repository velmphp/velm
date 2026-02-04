<?php

namespace Velm\Core\Compiler;

use ReflectionClass;

final class ModelAttributeExtractor
{
    /**
     * @throws \ReflectionException
     */
    public static function extract(string $class): array
    {
        $ref = new ReflectionClass($class);
        $attributes = [];

        foreach ($ref->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $property->setAccessible(true);
            $attributes[$property->getName()] = $property->getDefaultValue();
        }

        return $attributes;
    }
}
