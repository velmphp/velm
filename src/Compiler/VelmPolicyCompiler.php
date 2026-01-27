<?php

namespace Velm\Core\Compiler;

use Velm\Core\Compiler\Concerns\CompilesDomainFiles;

final class VelmPolicyCompiler extends BaseCompiler
{
    use CompilesDomainFiles;

    /**
     * @throws \ReflectionException
     */
    public function compile(bool $lazy = true): void
    {
        $groups = $this->discoverPolicies();

        foreach ($groups as $policy => $definitions) {
            $this->compileSingle($policy, definitions: $definitions, lazy: $lazy);
        }

        $this->compilePolicyMap($groups);
    }

    /**
     * @throws \ReflectionException
     */
    protected function discoverPolicies(): array
    {
        $policiesDir = 'Policies';

        return velm_compiler()->getDefinitionsMap($policiesDir);
    }

    protected function renderPolicy(string $policy, array $imports, array $pipelines): string
    {
        $uses = '';
        foreach ($imports as $alias => $fqcn) {
            $uses .= "use {$fqcn}".($alias !== class_basename($fqcn) ? " as {$alias}" : '').";\n";
        }

        $pipelinesCode = $this->renderPipelinesCode($pipelines);

        return <<<PHP
<?php

namespace Velm\\Policies;
use Velm\\Core\\Policies\\VelmPolicyProxy;

{$uses}
class {$policy} extends VelmPolicyProxy
{
{$pipelinesCode}
}
PHP;
    }

    /**
     * @throws \ReflectionException
     */
    protected function compilePolicyMap(array $groups): void
    {
        $map = [];

        foreach ($groups as $policy => $_) {
            $proxy = velm_compiler()->getProxyClass($policy);

            $model = str_replace('Policy', '', class_basename($policy));
            $map["Velm\\Models\\{$model}"] = $proxy;
        }

        file_put_contents(
            storage_path('framework/velm/policies.php'),
            "<?php\n\nreturn ".var_export($map, true).';'
        );
    }

    public function clearCache(): void
    {
        // TODO: Implement clearCache() method.
    }

    /**
     * @throws \ReflectionException
     */
    public function compileSingle(string $class, ?array $definitions = null, bool $lazy = false): void
    {
        if ($lazy) {
            $proxyClass = velm_compiler()->getProxyClass($class);

            if (! file_exists(velm_generated_path('policies.php'))) {
                $this->compilePolicyMap($this->discoverPolicies());
            }

            if (! $this->isStale($proxyClass)) {
                return;
            }
        }
        if (! $definitions) {
            $definitions = velm_compiler()->getDefinitions($class);
        }
        if (empty($definitions)) {
            return;
        }

        $imports = $this->compileImports($definitions);

        $pipelines = $this->collectPipelinesForBucket($definitions);

        $methods = $this->makePipelineMethodsCodeLines($pipelines);

        $generation_path = rtrim(storage_path('framework'.DIRECTORY_SEPARATOR.'velm'.DIRECTORY_SEPARATOR.'Policies'), '/\\');
        if (! is_dir($generation_path)) {
            mkdir($generation_path, 0755, true);
        }

        $policy = class_basename($class);
        file_put_contents(
            $generated_path = "{$generation_path}".DIRECTORY_SEPARATOR."{$policy}.php",
            $this->renderPolicy($policy, $imports, $pipelines, $methods)
        );

        // Pint if installed and allowed
        $this->runPintOnFile($generated_path);
    }
}
