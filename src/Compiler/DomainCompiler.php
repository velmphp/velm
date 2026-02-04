<?php

namespace Velm\Core\Compiler;

use ReflectionClass;
use ReflectionMethod;

class DomainCompiler
{
    /**
     * Compile fragments of a domain type into runtime anchors.
     *
     * @param  string[]  $fragments  Fully qualified class names
     * @param  DomainType  $domainType  'Models', 'Policies', 'Forms', 'Services' etc
     *
     * @throws \ReflectionException
     */
    public function compile(array $fragments, DomainType $domainType): void
    {
        $logical = $this->groupByShortName($fragments);

        $this->compileRuntimeAnchors($logical, $domainType);
        $this->compilePipelines($logical, $domainType);

        if ($domainType === DomainType::Models) {
            $this->compileRelationships($logical, $domainType);
        }
    }

    /**
     * @throws \ReflectionException
     */
    protected function groupByShortName(array $fragments): array
    {
        $logical = [];
        foreach ($fragments as $class) {
            $logical[(new ReflectionClass($class))->getShortName()][] = $class;
        }

        return $logical;
    }

    /**
     * @throws \ReflectionException
     */
    protected function compileRuntimeAnchors(array $logical, DomainType $domainType): void
    {
        $namespace = $domainType->namespace();

        foreach ($logical as $name => $classes) {
            $path = $domainType->path("{$name}.php");

            $imports = 'use Velm\Core\Runtime\PipelineExecutor;';
            $signature = "class {$name}";
            $traits = 'use PipelineExecutor;';
            $properties = '';

            if ($domainType === DomainType::Models) {
                $imports .= "\nuse Velm\\Core\\Eloquent\\RuntimeModel;";
                $signature .= ' extends RuntimeModel';
                $traits = '';

                $attributes = ModelAttributeCompiler::compile($classes);

                foreach ($attributes as $prop => $value) {
                    $export = var_export($value, true);
                    $properties .= "    protected \${$prop} = {$export};\n\n";
                }
            }

            file_put_contents($path, <<<PHP
<?php
namespace {$namespace};

{$imports}

{$signature}
{
{$properties}
    {$traits}
}
PHP
            );
        }
    }

    /**
     * @throws \ReflectionException
     */
    protected function compilePipelines(array $logical, DomainType $domainType): void
    {
        $pipelines = [];

        foreach ($logical as $short => $classes) {
            foreach ($classes as $class) {
                foreach ((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                    if ($method->isConstructor()) {
                        continue;
                    }
                    // If it is actually not defined in this class, skip it
                    if ($method->getDeclaringClass()->getName() !== $class) {
                        continue;
                    }
                    $methodName = $method->getName();

                    // Replace cross-module references with runtime class
                    $body = file_get_contents($method->getFileName());
                    foreach (DomainType::cases() as $type) {
                        foreach ($classes as $c) {
                            $shortClass = (new ReflectionClass($c))->getShortName();
                            $body = str_replace($c.'::class', "{$type->namespace("{$shortClass}::class")}", $body);
                        }
                    }

                    $pipelines[$domainType->namespace($short)][$methodName][] = $class;
                }
            }
        }

        file_put_contents(GeneratedPaths::base('pipelines.php'), '<?php return '.var_export($pipelines, true).';');
    }

    /**
     * @throws \ReflectionException
     */
    protected function compileRelationships(array $logical, DomainType $domainType): void
    {
        $relationships = [];

        foreach ($logical as $short => $classes) {
            foreach ($classes as $class) {
                foreach ((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                    $methodName = $method->getName();

                    // Only consider methods returning Eloquent\Relations
                    $refMethod = new ReflectionMethod($class, $methodName);
                    $returnType = (string) $refMethod->getReturnType();
                    if ($returnType && str_contains($returnType, 'Illuminate\\Database\\Eloquent\\Relations')) {
                        $relationships[$domainType->namespace("\\{$short}")][$methodName] =
                            DomainType::Models->namespace($returnType);
                    }
                }
            }
        }

        file_put_contents(GeneratedPaths::base('relationships.php'), '<?php return '.var_export($relationships, true).';');
    }
}
