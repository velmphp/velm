<?php

namespace Velm\Core\Ide;

use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use Velm\Core\Compiler\GeneratedPaths;
use Velm\Core\Pipeline\Contracts\Pipelinable;
use Velm\Core\Support\Helpers\ConsoleLogType;

class IdeGenerator
{
    /**
     * Generate all IDE stubs for all logical models
     */
    public function generate(): void
    {
        $this->clearGeneratedStubs();
        $logicalObjects = velm()->registry()->pipeline()->allStatic();

        foreach ($logicalObjects as $logicalName => $extensions) {
            try {
                $this->generateStubForLogicalObject($logicalName);
            } catch (\Throwable $exception) {
                velm_utils()->consoleLog(
                    "Error generating IDE stub for logical model {$logicalName}: ".$exception->getMessage(),
                    ConsoleLogType::ERROR
                );
            }
        }
    }

    protected function clearGeneratedStubs(): void
    {
        // Clear the existing stubs directory before generating new ones to avoid stale stubs for removed logical models
        $stubsDir = GeneratedPaths::base('ide-stubs');
        if (is_dir($stubsDir)) {
            // Recursively delete all files in the stubs directory
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($stubsDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($files as $file) {
                if ($file->isDir()) {
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }
        }
    }

    /**
     * Generate one stub file for a single logical model
     *
     * @throws \ReflectionException
     */
    protected function generateStubForLogicalObject(string $logicalName, array $extensions = []): void
    {
        if (empty($extensions)) {
            $extensions = velm()->registry()->pipeline()->findStatic($logicalName);
        }

        $methods = [];
        $properties = [];
        $reflections = collect($extensions)->mapWithKeys(function ($extension) {
            $reflection = new ReflectionClass($extension);

            return [$extension => $reflection];
        })->all();

        foreach ($reflections as $extensionClass => $reflection) {
            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ($this->shouldIncludeMethod($method, $extensionClass, $methods)) {
                    $name = $method->getName();
                    $params = $this->buildParameterList($method);
                    $returnType = $this->buildReturnType($method);

                    $paramString = implode(', ', $params);

                    // For scopeMethods e.g scopeActiveOnly, rename to activeOnly for better readability in the IDE. Also return type must be QueryBuilder always
                    if (str_starts_with($name, 'scope')) {
                        $name = lcfirst(substr($name, 5));
                        $returnType = '\\Illuminate\\Database\\Eloquent\\Builder';
                        // The first parameter type must also be Builder, but we can omit it in the docblock since it's implied for scope methods
                        if (! empty($params)) {
                            array_shift($params);
                            $paramString = implode(', ', $params);
                        }
                    }

                    // For attributes e.g getFullNameAttribute, include a snake_case property as well for better readability in the IDE, and return type must be the attribute type
                    if (str_ends_with($name, 'Attribute')) {
                        $propertyName = \Str::snake(substr($name, 3, -9));
                        $properties[] = "@property-read {$returnType} \${$propertyName}";
                    }

                    // For void return type, we can omit the return type in the docblock since it's implied
                    if (empty($returnType)) {
                        $methods[$name] = "@method {$name}({$paramString})";
                    } else {
                        $methods[$name] = "@method {$returnType} {$name}({$paramString})";
                    }
                }
            }
            velm_utils()->consoleLog("$logicalName ($extensionClass)", ConsoleLogType::NOTE);
        }
        $methodsDoc = implode("\n * ", $methods);
        $propertiesDoc = implode("\n * ", $properties);

        $this->writeStub($logicalName, [
            'methodsDoc' => $methodsDoc,
            'propertiesDoc' => $propertiesDoc,
            'class' => $logicalName,
        ]);
    }

    protected function shouldIncludeMethod(
        ReflectionMethod $method,
        string $extensionClass,
        array $methods
    ): bool {
        if ($method->isConstructor()) {
            return false;
        }

        if (str_starts_with($method->getName(), '__')) {
            return false;
        }

        if ($method->getDeclaringClass()->getName() !== $extensionClass) {
            return false;
        }

        if (array_key_exists($method->getName(), $methods)) {
            return false;
        }

        return true;
    }

    /**
     * @throws \ReflectionException
     */
    protected function buildParameterList(ReflectionMethod $method): array
    {
        $params = [];
        $methodParams = $method->getParameters();
        foreach ($methodParams as $index => $param) {
            if ($this->shouldIncludeParameter($param, $index)) {
                $type = $this->processType($param->getType());
                $default = $param->isDefaultValueAvailable()
                    ? ' = '.var_export($param->getDefaultValue(), true)
                    : '';
                $params[] = "{$type} \${$param->getName()}{$default}";
            }
        }

        return $params;
    }

    /**
     * @throws \ReflectionException
     */
    protected function shouldIncludeParameter(ReflectionParameter $param, int $index): bool
    {
        if ($index !== 0) {
            return true;
        }
        if ($param->getPosition() !== 0) {
            return true;
        }

        if ($param->getName() === 'self') {
            return false;
        }

        if ($param->hasType()) {
            $type = $param->getType();

            if ($type instanceof \ReflectionNamedType && class_exists($type->getName())) {
                $class = new ReflectionClass($type->getName());

                return ! $class->implementsInterface(Pipelinable::class);
            }
        }

        return true;
    }

    protected function buildReturnType(ReflectionMethod $method): string
    {
        if (! $method->hasReturnType()) {
            return '';
        }

        return $this->processType($method->getReturnType());
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
        $stub = file_get_contents($this->getStubPath($logicalName));

        // All replacements, considering both {{placeholder}} and {{ placeholder }} formats
        foreach ($replacements as $key => $value) {
            $stub = str_replace(["{{{$key}}}", "{{ {$key} }}"], $value, $stub);
        }

        return $stub;
    }

    private function processType(?\ReflectionType $type): string
    {
        if ($type === null) {
            return 'mixed';
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

        // if type is a class namespace, prepend \\ to make it a fully qualified class name in the docblock
        if (str_contains($type->getName(), '\\') && ! str_starts_with($type->getName(), '\\')) {
            return '\\'.$type->getName();
        }

        // Process nullable types by appending |null to the type name
        if ($type->allowsNull() && $type->getName() !== 'null') {
            return $type->getName().'|null';
        }

        return $type->getName();
    }

    protected function getOutputPath(string $logicalName): string
    {
        $relative = 'Models';
        if (str_ends_with($logicalName, 'Policy')) {
            $relative = 'Policies';
        } elseif (str_contains($logicalName, 'Service')) {
            $relative = 'Services';
        }

        return GeneratedPaths::base("ide-stubs/{$relative}");
    }
}
