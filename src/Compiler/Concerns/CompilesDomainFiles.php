<?php

namespace Velm\Core\Compiler\Concerns;

use LogicException;
use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use ReflectionClass;
use ReflectionException;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use RuntimeException;
use Symfony\Component\Process\Process;
use Velm\Core\Compiler\VelmClassReferenceCollector;
use Velm\Core\Contracts\VelmClassContract;
use Velm\Core\Domain\Models\VelmModel;
use Velm\Core\Domain\Models\VelmModelProxy;
use Velm\Core\Domain\Policies\VelmPolicy;
use Velm\Core\Domain\Policies\VelmPolicyProxy;
use Velm\Core\Modules\ModuleDescriptor;

use function Laravel\Prompts\warning;

trait CompilesDomainFiles
{
    protected function resolveClassFromFile(string $file): string
    {
        $src = file_get_contents($file);

        preg_match('/namespace\s+([^;]+);/', $src, $ns);
        preg_match('/class\s+([^\s]+)/', $src, $cls);

        return ($ns[1] ?? '').'\\'.($cls[1] ?? '');
    }

    /**
     * @return array<string, array<int, array{class: string, method: ReflectionMethod}>>
     *
     * @throws ReflectionException
     */
    protected function collectPipelinesForBucket(array $sortedClasses): array
    {
        $pipelines = [];
        foreach ($sortedClasses as $class) {

            // Ensure the class does not reference foreign modules
            $this->ensureNoForeignClasses($class);

            $rc = new ReflectionClass($class);

            foreach ($rc->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED) as $method) {

                // only methods declared directly on this definition
                if ($method->getDeclaringClass()->getName() !== $class) {
                    continue;
                }

                if ($method->isStatic()) {
                    continue;
                }

                $name = $method->getName();

                $pipelines[$name][] = [
                    'class' => $class,
                    'method' => $method,
                ];
            }
        }

        return $pipelines;
    }

    /**
     * @throws ReflectionException
     */
    protected function compileImports(array $classes): array
    {
        $imports = [];

        foreach ($classes as $class) {
            $file = (new ReflectionClass($class))->getFileName();
            if (! $file) {
                continue;
            }
            $fileImports = $this->extractUseStatements($file);
            foreach ($this->extractUseStatements($file) as $alias => $fqcn) {
                // any imports from Module level VelmModel should be replaced with Velm\Models\{ClassName}
                if (is_subclass_of($fqcn, VelmModel::class) && ! is_subclass_of($fqcn, VelmModelProxy::class)) {
                    $fqcn = '\\'.'Velm\\Models\\'.class_basename($fqcn);
                }
                if (filled($fqcn)) {
                    $imports[$alias] = $fqcn;
                }
            }
        }

        return $imports;
    }

    protected function extractUseStatements(string $filePath): array
    {
        $code = file_get_contents($filePath);
        $tokens = token_get_all($code);

        $imports = [];

        $braceLevel = 0;
        $collecting = false;

        $fqn = null;
        $alias = null;
        $expectAlias = false;

        foreach ($tokens as $token) {
            if (is_string($token)) {
                if ($token === '{') {
                    $braceLevel++;
                } elseif ($token === '}') {
                    $braceLevel--;
                }

                if ($collecting && $token === ';') {
                    if ($fqn !== null) {
                        $alias ??= basename(str_replace('\\', '/', $fqn));
                        $imports[$alias] = $fqn;
                    }

                    // reset state
                    $collecting = false;
                    $fqn = null;
                    $alias = null;
                    $expectAlias = false;
                }

                continue;
            }

            [$type, $value] = $token;

            // Only top-level import uses
            if ($type === T_USE && $braceLevel === 0) {
                $collecting = true;
                $fqn = null;
                $alias = null;
                $expectAlias = false;

                continue;
            }

            if (! $collecting) {
                continue;
            }

            // Skip whitespace
            if ($type === T_WHITESPACE) {
                continue;
            }

            // Namespace (PHP 8+)
            if (in_array($type, [
                T_NAME_QUALIFIED,
                T_NAME_FULLY_QUALIFIED,
                T_NAME_RELATIVE,
            ], true)) {
                $fqn = ltrim($value, '\\');

                continue;
            }

            // Older PHP fallback
            if ($type === T_STRING && $fqn === null) {
                $fqn = $value;

                continue;
            }

            // Alias keyword
            if ($type === T_AS || strtolower($value) === 'as') {
                $expectAlias = true;

                continue;
            }

            // Alias name
            if ($expectAlias && $type === T_STRING) {
                $alias = $value;
                $expectAlias = false;
            }
        }

        return $imports;
    }

    protected function reconstructMethod(ReflectionMethod $method, bool $asClosure = true): string
    {
        $body = $this->extractMethodBody($method);
        // replace $this->super(...) → $super(...)
        $body = preg_replace('/\$this->super\s*\((.*?)\)/', '$super($1)', $body);
        $params = $this->generateParams($method);
        $return = $this->returnTypeSignature($method);

        if ($asClosure) {
            return "function (callable \$super{$params}){$return} {\n".
                $this->indent($body, 3)."\n".
                $this->indent('}', 2);
        }
        $signature = $this->methodSignature($method);
        if ($method->isAbstract()) {
            return $signature;
        }

        return $signature."\n{\n".
            $this->indent($body, 2)."\n".
            '}';
    }

    protected function generateParams(ReflectionMethod $method): string
    {
        $params = [];

        // The type of any model parameter must be replaced with the generated model class

        foreach ($method->getParameters() as $param) {
            $code = '';

            if ($param->hasType()) {
                $type = $param->getType()->getName();
                // if it is a class, prepend with \
                // If the type is a class inside a module, replace with the generated model
                $isClass = class_exists($type) || interface_exists($type);
                if ($isClass) {
                    $type = '\\'.ltrim($type, '\\');
                }
                $isModuleModel = $isClass && is_subclass_of($type, VelmModel::class) && ! is_subclass_of($type, VelmModelProxy::class);

                if ($isModuleModel) {
                    // replace with \Velm\Models\{ClassName}
                    $code .= '\\'.'Velm\\Models\\'.class_basename($type).' ';
                } else {
                    $code .= $type.' ';
                }
            }

            if ($param->isVariadic()) {
                $code .= '...';
            }

            $code .= '$'.$param->getName();

            if ($param->isDefaultValueAvailable()) {
                $code .= ' = '.var_export($param->getDefaultValue(), true);
            }

            $params[] = $code;
        }

        return count($params) ? ', '.implode(', ', $params) : '';
    }

    protected function extractMethodBody(ReflectionMethod $method): string
    {
        $file = file($method->getFileName());
        $slice = array_slice(
            $file,
            $method->getStartLine() - 1,
            $method->getEndLine() - $method->getStartLine() + 1
        );

        $src = implode('', $slice);
        $tokens = token_get_all('<?php '.$src);

        $body = '';
        $depth = 0;
        $record = false;

        foreach ($tokens as $token) {
            $value = is_array($token) ? $token[1] : $token;

            if ($value === '{') {
                $depth++;
                if ($depth === 1) {
                    $record = true;

                    continue;
                }
            }

            if ($value === '}') {
                $depth--;
                if ($depth === 0) {
                    break;
                }
            }

            if ($record) {
                $body .= $value;
            }
        }

        return trim($body);
    }

    protected function extractSignatureFromClosure(string $closure): string
    {
        preg_match('/function\s*\((.*?)\)\s*(:\s*[^{]+)?\s*\{/', $closure, $m);

        return '('.($m[1] ?? '').')'.($m[2] ?? '');
    }

    /* ================= SIGNATURE HELPERS ================= */

    protected function methodSignature(ReflectionMethod $method): string
    {
        $params = $this->generateParams($method);
        $params = ltrim($params, ', ');

        $return = $this->returnTypeSignature($method);
        $visibility = $method->isPublic() ? 'public ' : ($method->isProtected() ? 'protected ' : 'private ');
        $static = $method->isStatic() ? 'static ' : '';

        if ($method->isAbstract()) {
            return "{$visibility}{$static}abstract function {$method->getName()}({$params}){$return};";
        }

        return "{$visibility}{$static}function {$method->getName()}({$params}){$return}";
    }

    protected function parameterSignature(ReflectionParameter $param): string
    {
        $sig = '';

        if ($param->hasType()) {
            $sig .= $this->typeSignature($param->getType()).' ';
        }

        if ($param->isPassedByReference()) {
            $sig .= '&';
        }
        if ($param->isVariadic()) {
            $sig .= '...';
        }

        $sig .= '$'.$param->getName();

        if ($param->isDefaultValueAvailable()) {
            $sig .= ' = '.$this->exportDefault($param->getDefaultValue());
        }

        return $sig;
    }

    protected function returnTypeSignature(ReflectionMethod $method): string
    {
        if (! $method->hasReturnType()) {
            return ' :mixed';
        }

        return ' :'.$this->typeSignature($method->getReturnType());
    }

    protected function typeSignature(ReflectionUnionType|ReflectionNamedType|ReflectionIntersectionType $type): string
    {
        if ($type instanceof ReflectionUnionType) {
            return implode('|', array_map(
                fn ($t) => $this->typeSignature($t),
                $type->getTypes()
            ));
        }

        // intersection type
        if ($type instanceof ReflectionIntersectionType) {
            return implode('&', array_map(
                fn ($t) => $this->typeSignature($t),
                $type->getTypes()
            ));
        }

        // If void, return void
        if ($type->getName() === 'void') {
            return 'void';
        }
        $typeName = $type->getName();
        // If null, return null
        if ($typeName === 'null') {
            return 'null';
        }
        // If builtin, return as is
        if ($type->isBuiltin()) {
            // If allows null, prefix with ?
            if ($type->allowsNull() && $typeName !== 'mixed') {
                return '?'.$typeName;
            }

            return $typeName;
        }

        // Replace any return type that points to a class inside a module with its compiled version
        if (class_exists($typeName) || interface_exists($typeName)) {
            // Replace models
            if (is_subclass_of($typeName, VelmModel::class) && ! is_subclass_of($typeName, VelmModelProxy::class)) {
                $return = '\\'.'Velm\\Models\\'.class_basename($typeName);
            } // Replace policies
            elseif (is_subclass_of($typeName, VelmPolicy::class) && ! is_subclass_of($typeName, VelmPolicyProxy::class)) {
                $return = '\\'.'Velm\\Policies\\'.class_basename($typeName);
            } // TODO: Add other Velm-specific compiled domain classes here
            else {
                $return = '\\'.ltrim($typeName, '\\');
            }

            return $return;
        }

        // Fallback: return the type name
        return $typeName;
    }

    protected function indent(string $code, int $level = 1): string
    {
        $pad = str_repeat('    ', $level);

        return implode("\n", array_map(fn ($l) => $pad.$l, explode("\n", $code)));
    }

    /**
     * To enforce true module autonomy, ensure that no classes from other modules are referenced.
     * This makes the module truly decoupled.
     *
     * @throws ReflectionException
     * @throws \JsonException
     */
    protected function ensureNoForeignClasses(string $sourceClass): void
    {
        $sourceModule = $sourceClass::velm()->module;
        if (! $sourceModule) {
            return;
        }

        $ref = new ReflectionClass($sourceClass);
        $file = $ref->getFileName();
        if (! $file || ! is_file($file)) {
            return;
        }

        $parser = new ParserFactory()->createForNewestSupportedVersion();

        try {
            $ast = $parser->parse(file_get_contents($file));
        } catch (Error) {
            return;
        }

        $collector = new VelmClassReferenceCollector;

        $traverser = new NodeTraverser;
        $traverser->addVisitor(new NameResolver);
        $traverser->addVisitor($collector);
        $traverser->traverse($ast);

        foreach (array_keys($collector->classes) as $refClass) {
            $refClass = ltrim($refClass, '\\');
            // Allowed namespaces
            if (
                str_starts_with($refClass, 'Velm\\Core') ||
                str_starts_with($refClass, 'Velm\\Models') ||
                str_starts_with($refClass, 'Velm\\Policies')
            ) {
                continue;
            }
            $refModule = \Velm::registry()->modules()->findForClass($refClass);
            if (! $refModule) {
                // Try the available modules
                $available = \Velm::registry()->modules()->findForClassInAvailable($refClass);
                if ($available) {
                    $namespace = $available['namespace'] ?? 'unknown';
                    // Throw that this is referencing a class from an unavailable module
                    throw new RuntimeException(
                        "Module '{$sourceModule->packageName}' cannot reference class '{$refClass}' from unavailable module '{$available['packageName']}' (namespace: '{$namespace}'). "
                        .'Ensure that the module is installed and enabled.'
                    );
                }

                continue;
            }

            $reflection = new ReflectionClass($refClass);

            // Skip abstracts
            if ($reflection->isAbstract()) {
                continue;
            }

            // Must belong to a Velm module
            if (! is_subclass_of($refClass, VelmClassContract::class)) {
                continue;
            }

            $refModule = $refClass::velm()->module ?? null;

            if ($refModule && $refModule->packageName !== $sourceModule->packageName) {
                $ns = $reflection->getName();
                $recommendedNs = str_replace(
                    $refModule->namespace,
                    $sourceModule->namespace,
                    $ns
                );

                throw new RuntimeException(
                    "Module '{$sourceModule->packageName}' cannot reference class '{$refClass}' from module '{$refModule->packageName}'. "
                    ."This violates module autonomy; extend '{$ns}' in your module as '{$recommendedNs}' and use that instead."
                );
            }
        }
    }

    protected function runPintOnFile(string $filePath): void
    {
        if (! config('velm.compiler.use_pint', false)) {
            return;
        }
        // First check if pint is installed
        if (! file_exists('vendor/bin/pint')) {
            warning('PHP Pint is not installed. Skipping code formatting.');

            return;
        }
        $process = new Process(['vendor/bin/pint', $filePath]);
        $process->run();
        if (! $process->isSuccessful()) {
            throw new RuntimeException("PHP Pint failed on file {$filePath}: ".$process->getErrorOutput());
        }
    }

    /**
     * @param  array<string, array<int, array{class: string, method: ReflectionMethod}>>  $pipelines
     */
    protected function makePipelinesBootCodeLines(array $pipelines): array
    {
        $code = [];

        // Boot method
        $code[] = '    protected static function bootVelmPipelines(): void';
        $code[] = '    {';
        foreach ($pipelines as $method => $entries) {

            // Reverse order for super()
            $entries = array_reverse($entries);

            foreach ($entries as $i => $entry) {

                $rm = $entry['method'];

                $code[] = sprintf(
                    '        static::$_velmPipelines[%s][%d] = %s;',
                    var_export($method, true),
                    $i,
                    $this->reconstructMethod($rm)
                );
            }
        }

        $code[] = '    }';
        $code[] = '';

        return $code;
    }

    /**
     * @param  array<string, array<int, array{class: string, method: ReflectionMethod}>>  $pipelines
     */
    protected function makePipelineMethodsCodeLines(array $pipelines): array
    {
        $code = [];
        // generate methods
        foreach ($pipelines as $method => $entries) {

            $signatureMethod = $this->resolveSignature($entries);
            $signature = $this->methodSignature($signatureMethod);
            $isVoid = $signatureMethod->hasReturnType() && $signatureMethod->getReturnType()->getName() === 'void';

            $code[] = '    '.$signature;
            $code[] = '    {';
            if ($isVoid) {
                $code[] = '        static::invokePipeline(\''.$method.'\', func_get_args());';
            } else {
                $code[] = '        return static::invokePipeline(\''.$method.'\', func_get_args());';
            }
            $code[] = '    }';
            $code[] = '';
        }

        return $code;
    }

    protected function renderPipelinesCode(array $pipelines): string
    {
        $bootLines = $this->makePipelinesBootCodeLines($pipelines);

        // generate methods
        $methodLines = $this->makePipelineMethodsCodeLines($pipelines);
        $code = [...$bootLines, ...$methodLines];

        return implode("\n", $code);
    }

    /**
     * @param  array<array{class: string, method: ReflectionMethod}>  $pipeline
     */
    protected function resolveSignature(array $pipeline): ReflectionMethod
    {
        if (empty($pipeline)) {
            throw new LogicException('Cannot resolve signature from empty pipeline');
        }

        return $pipeline[0]['method'];
    }

    protected function substituteDomainClassNamespaces(ModuleDescriptor $module, string $code): string
    {
        $moduleNs = trim($module->namespace, '\\');
        $replacements = [
            "{$moduleNs}\\Models" => trim(config('velm.compiler.generated_namespaces.Models'), '\\'),
            "{$moduleNs}\\Policies" => trim(config('velm.compiler.generated_namespaces.Policies'), '\\'),
        ];

        // do bulk replacement
        return str_replace(array_keys($replacements), array_values($replacements), $code);
    }
}
