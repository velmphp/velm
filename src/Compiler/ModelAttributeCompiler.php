<?php

namespace Velm\Core\Compiler;

use RuntimeException;

final class ModelAttributeCompiler
{
    /**
     * @param  string[]  $fragments
     *
     * @throws \ReflectionException
     */
    public static function compile(array $fragments): array
    {
        $compiled = [];

        foreach ($fragments as $class) {
            $attributes = ModelAttributeExtractor::extract($class);

            foreach ($attributes as $name => $value) {

                // Mergeable arrays
                if (in_array($name, ModelAttributeRules::MERGEABLE, true)) {
                    if (! is_array($value)) {
                        throw new RuntimeException(
                            "Model attribute \${$name} must be an array ({$class})"
                        );
                    }

                    $compiled[$name] ??= [];
                    $compiled[$name] = array_replace_recursive(
                        $compiled[$name],
                        $value
                    );

                    continue;
                }

                // Singleton values
                if (in_array($name, ModelAttributeRules::SINGLETON, true)) {
                    if (! array_key_exists($name, $compiled)) {
                        $compiled[$name] = $value;

                        continue;
                    }

                    if ($compiled[$name] !== $value) {
                        throw new RuntimeException(
                            "Conflicting model attribute \${$name} detected"
                        );
                    }
                }
            }
        }

        return $compiled;
    }
}
