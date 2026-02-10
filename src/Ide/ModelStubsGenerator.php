<?php

namespace Velm\Core\Ide;

use ReflectionClass;
use ReflectionMethod;
use Velm\Core\Compiler\GeneratedPaths;

class ModelStubsGenerator
{
    protected string $outputPath;

    public function __construct(?string $outputPath = null)
    {
        $this->outputPath = $outputPath ?? GeneratedPaths::base('ide-stubs/models');
    }

    /**
     * Generate all IDE stubs for all logical models
     */
    public function generate(): void
    {
        $logicalModels = velm()->registry()->pipeline()->allStatic();

        foreach ($logicalModels as $logicalName => $extensions) {
            $this->generateStubForLogicalModel($logicalName, $extensions);
        }
    }

    /**
     * Generate one stub file for a single logical model
     */
    protected function generateStubForLogicalModel(string $logicalName, array $extensions = []): void
    {
        if (empty($extensions)) {
            $extensions = velm()->registry()->pipeline()->findStatic($logicalName);
        }
        $extensions = velm()->registry()->pipeline()->findStatic($logicalName);

        $methods = [];
        foreach ($extensions as $extensionClass) {
            $reflection = new ReflectionClass($extensionClass);

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->isConstructor() || str_starts_with($method->name, '__')) {
                    continue;
                }

                // Generate only those methods that are declared in the extension class itself, not inherited ones
                if ($method->getDeclaringClass()->getName() !== $extensionClass) {
                    continue;
                }

                $name = $method->name;
                // If the method already exists in the list (from a previous extension), we can skip it since the first registered extension takes precedence in the pipeline
                if (array_key_exists($name, $methods)) {
                    continue;
                }
                $params = [];

                foreach ($method->getParameters() as $index => $param) {
                    // Skip the first $self param
                    if ($index === 0) {
                        // First parameter MUST always be $self, but we can skip it in the docblock since it's implied
                        continue;
                    }

                    // Consider union and intersection types as well, we can use the __toString method of ReflectionType to get the full type declaration

                    $type = $this->processType($param->getType());

                    $default = $param->isDefaultValueAvailable() ? ' = '.var_export($param->getDefaultValue(), true) : '';
                    $params[] = "{$type} \${$param->getName()}{$default}";
                }

                $paramString = implode(', ', $params);

                // For void return type, we can omit the return type in the docblock since it's implied
                if (! $method->hasReturnType()) {
                    $returnType = '';
                } else {
                    $returnType = $this->processType($method->getReturnType());
                }

                $methods[$name] = "@method {$returnType} {$name}({$paramString})";
            }
        }

        $methodsDoc = implode("\n * ", $methods);

        $this->writeStub($logicalName, ['methodsDoc' => $methodsDoc, 'class' => $logicalName]);
    }

    /**
     * Write the stub file to storage
     */
    protected function writeStub(string $logicalName, array $replacements): void
    {
        $dir = $this->getOutputPath($logicalName);
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $file = "{$dir}/{$logicalName}.php";
        $content = $this->getStub($logicalName, $replacements);

        // Atomic write to prevent race conditions
        $tempFile = tempnam(sys_get_temp_dir(), 'stub');
        file_put_contents($tempFile, $content);
        rename($tempFile, $file);
    }

    protected function getStubPath(string $logicalName): string
    {
        $relative = 'model';
        if (str_ends_with($logicalName, 'Policy')) {
            $relative = 'policy';
        } elseif (str_ends_with($logicalName, 'Service')) {
            $relative = 'service';
        }

        return __DIR__."/stubs/{$relative}.stub";
    }

    protected function getStub(string $logicalName, array $replacements): string
    {
        $stub = file_get_contents(__DIR__.'/stubs/model.stub');
        foreach ($replacements as $key => $value) {
            $stub = str_replace("{{ {$key} }}", $value, $stub);
        }

        return $stub;
    }

    private function processType(?\ReflectionType $type): string
    {
        if ($type === null) {
            return 'mixed';
        }
        if ($type->allowsNull() && ! str_contains((string) $type, 'null')) {
            return $this->processType($type).'|null';
        }

        // For union and intersection types, we will recursively process each type and join them with | or & respectively

        if ($type instanceof \ReflectionUnionType) {
            return implode('|', array_map([$this, 'processType'], $type->getTypes()));
        }
        if ($type instanceof \ReflectionIntersectionType) {
            return implode('&', array_map([$this, 'processType'], $type->getTypes()));
        }

        // Void return type can be omitted in the docblock since it's implied
        if ($type->getName() === 'void') {
            return '';
        }

        // If type is a class in the same namespace, we can use a relative class name in the docblock
        if (str_starts_with($type->getName(), 'Velm\\Models\\')) {
            return class_basename($type->getName());
        }

        // if type is a class namespace, prepend \\ to make it a fully qualified class name in the docblock
        if (str_contains($type->getName(), '\\') && ! str_starts_with($type->getName(), '\\')) {
            return '\\'.$type->getName();
        }

        return $type->getName();
    }

    protected function getOutputPath(string $logicalName): string
    {
        $relative = 'models';
        if (str_ends_with($logicalName, 'Policy')) {
            $relative = 'policies';
        } elseif (str_contains($logicalName, 'Service')) {
            $relative = 'services';
        }

        return GeneratedPaths::base("ide-stubs/{$relative}");
    }
}
