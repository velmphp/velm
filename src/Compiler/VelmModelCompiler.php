<?php

namespace Velm\Core\Compiler;

use Velm\Core\Compiler\Concerns\CompilesDomainFiles;
use Velm\Core\Contracts\VelmCompilerContract;
use Velm\Core\Domain\Models\VelmModel;
use Velm\Core\Support\Constants;

class VelmModelCompiler extends BaseCompiler implements VelmCompilerContract
{
    use CompilesDomainFiles;

    private string $outputPath;

    public function __construct(
    ) {
        $this->outputPath = GeneratedPaths::models();
    }

    /**
     * @throws \ReflectionException
     */
    public function compile(bool $lazy = true): void
    {
        $map = \Velm::registry()->models()->definitionsMap();
        foreach ($map as $logicalName => $_) {
            $definitions = \Velm::registry()->models()->definitions($logicalName);
            \Laravel\Prompts\info("Generating proxy for model: {$logicalName}");
            //            $this->compileSingle($definitions[0], definitions: $definitions, lazy: $lazy);
            app(DomainCompiler::class)->compile($definitions, domainType: DomainType::Models);
        }
    }

    /**
     * @param  class-string<VelmModel>  $class
     * @param  array<class-string<VelmModel>>|null  $definitions
     *
     * @throws \ReflectionException
     */
    public function compileSingle(string $class, ?array $definitions = null, bool $lazy = false): void
    {
        $logicalName = $class::velm()->name;
        if ($lazy) {
            $proxyClass = \Velm::registry()->models()->proxy($logicalName);
            if (! $this->isStale($proxyClass)) {
                return;
            }
        }
        if (! $definitions) {
            $definitions = \Velm::registry()->models()->definitions($logicalName);
        }
        if (empty($definitions)) {
            return;
        }

        $imports = $this->compileImports($definitions);
        $pipelines = $this->collectPipelinesForBucket($definitions);
        $attributes = $this->compileModelAttributes($definitions);

        $generation_path = $this->outputPath;
        if (! is_dir($generation_path)) {
            mkdir($generation_path, 0755, true);
        }
        /**
         * @var class-string<VelmModel> $class
         */
        $path = $class::velm()->getProxyCandidatePath();
        $code = $this->renderModel($class, $imports, $pipelines, $attributes);

        // Atomic writes to avoid race conditions
        $tempPath = $path.'.tmp';
        // get dir, if it does not exist create it
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($tempPath, $code);
        rename($tempPath, $path);
    }

    /* ================= CLASS ================= */

    /**
     * @param  class-string<VelmModel>  $baseClass
     *
     * @throws \ReflectionException
     */
    protected function renderModel(string $baseClass, array $imports, array $pipelines, array $attributes): string
    {
        // For each imports, pipelines and attributes, replace any occurrence of a module class with a generated namespace
        // At this point we have already eliminated all unwanted imports, the only modular imports that exist are from the current module

        $module = $baseClass::module();

        $className = class_basename($baseClass);
        $importsCode = '';
        foreach ($imports as $alias => $fqcn) {
            $importsCode .= "use {$fqcn}".($alias !== class_basename($fqcn) ? " as {$alias}" : '').";\n";
        }
        $importsCode = $this->substituteDomainClassNamespaces($module, $importsCode);

        $attributesCode = $this->substituteDomainClassNamespaces($module, $this->renderModelAttributes($attributes));
        // scan the attributes code and
        $pipelinesCode = $this->substituteDomainClassNamespaces($module, $this->renderPipelinesCode($pipelines));
        $logicalName = $baseClass::getName();
        $baseNs = new \ReflectionClass($baseClass)->getNamespaceName();
        // Get relative name of the base class and append it to Velm\Models
        $relativeNamespace = str($baseNs)->replace($baseClass::module()->entryPoint::getNamespaceFromPath($baseClass::module()->entryPoint::getModelsPath()), '')->trim('\\')->toString();
        $generatedNamespace = rtrim(config('velm.compiler.generated_namespaces.Models'), '\\').'\\'.$relativeNamespace;
        $generatedNamespace = trim($generatedNamespace, '\\');

        return <<<PHP
<?php
namespace {$generatedNamespace};
use Velm\Core\Domain\Models\VelmModelProxy;
{$importsCode}
class {$className} extends VelmModelProxy
{
    public static string \$logicalName = '{$logicalName}';
{$attributesCode}
{$pipelinesCode}
}
PHP;
    }

    /* ================= COLLECTORS ================= */

    private function renderArray(string $signature, array $values): string
    {
        if (array_keys($values) === range(0, count($values) - 1)) {
            // This has numeric keys only, export as values without the keys
            // instead of normal export, use custom export to export only values
            $export = "[\n";
            foreach ($values as $value) {
                $export .= '        '.var_export($value, true).",\n";
            }
            $export .= '    ]';

            return <<<PHP
    $signature = {$export};
PHP;
        }

        $export = var_export($values, true);
        // Use square brackets for arrays
        $export = str_replace(['array (', ')'], ['[', ']'], $export);
        // Indent the inner body if the array is multiline and is not empty
        // Indent only from the second line
        $lines = explode("\n", $export);
        if (count($lines) > 2) {
            $indentedLines = [];
            foreach ($lines as $index => $line) {
                if ($index === 0) {
                    $indentedLines[] = " $line";
                } elseif ($index === count($lines) - 1) {
                    $indentedLines[] = $this->indent($line);
                } else {
                    $indentedLines[] = $this->indent($line, 2);
                }
            }
            $export = implode("\n", $indentedLines);
        }

        return <<<PHP
    $signature = {$export};
PHP;
    }

    /* ================= MODEL ATTRIBUTES ================= */
    /**
     * @throws \ReflectionException
     */
    protected function compileModelAttributes(array $definitions): array
    {
        $attributes = [];

        foreach ($definitions as $definition) {
            $reflection = new \ReflectionClass($definition);
            $instance = $reflection->newInstanceWithoutConstructor();

            // Array attributes → merge
            foreach (Constants::ARRAY_MODEL_ATTRIBUTES as $attr) {
                if (! $reflection->hasProperty($attr)) {
                    continue;
                }

                $property = $reflection->getProperty($attr);
                // Get the declaring instance
                if ($property->getDeclaringClass()->getName() !== $reflection->getName()) {
                    continue;
                }

                $value = $property->getValue($instance);

                if (! is_array($value)) {
                    continue;
                }

                // if numeric keys, return array values

                if (array_keys($value) === range(0, count($value) - 1)) {
                    $value = array_values($value);
                }

                $attributes[$attr] ??= [];
                $attributes[$attr] = array_merge($attributes[$attr], $value);
            }

            // Primitive attributes → overwrite
            foreach (Constants::PRIMITIVE_MODEL_ATTRIBUTES as $attr) {
                if (! $reflection->hasProperty($attr)) {
                    continue;
                }

                $property = $reflection->getProperty($attr);
                if ($property->getDeclaringClass()->getName() !== $reflection->getName()) {
                    continue;
                }

                $attributes[$attr] = $property->getValue($instance);
            }
        }

        return $attributes;
    }

    protected function renderModelAttributes(array $attributes): string
    {
        $code = '';

        foreach ($attributes as $name => $value) {
            $visibility = in_array($name, ['timestamps', 'incrementing'])
                ? 'public'
                : 'protected';

            $signature = $visibility.' $'.$name;
            if (is_array($value)) {
                $export = $this->renderArray($signature, $value);
            } else {
                $export = var_export($value, true);
                $export = <<<PHP
    $signature = {$export};
PHP;

            }

            $code .= $export;
            $code .= "\n\n";
        }

        return $code;
    }

    public function clearCache(): void
    {
        // TODO: Implement clearCache() method.
    }
}
