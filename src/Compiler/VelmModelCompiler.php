<?php

namespace Velm\Core\Compiler;

use DB;
use Illuminate\Database\Eloquent\Relations\Relation;
use ReflectionClass;
use ReflectionMethod;
use Velm\Core\Compiler\Concerns\CompilesDomainFiles;
use Velm\Core\Contracts\VelmCompilerContract;
use Velm\Core\Domain\Models\VelmModel;
use Velm\Core\Support\Constants;

class VelmModelCompiler implements VelmCompilerContract
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

        foreach ($map as $logicalName => $definitions) {
            \Laravel\Prompts\info("Generating proxy for model: {$logicalName}");
            $this->compileSingle($logicalName, definitions: $definitions, lazy: $lazy);
        }
    }

    /**
     * @throws \ReflectionException
     */
    public function compileSingle(string $logicalName, ?array $definitions = null, bool $lazy = false): void
    {
        if ($lazy) {
            $proxyClass = \Velm::registry()->models()->proxies($logicalName);
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
        file_put_contents($tempPath, $code);
        rename($tempPath, $path);
    }

    /* ================= CLASS ================= */

    /**
     * @param  class-string<VelmModel>  $baseClass
     */
    protected function renderModel(string $baseClass, array $imports, array $pipelines, array $attributes): string
    {
        $className = class_basename($baseClass);
        $importsCode = '';
        foreach ($imports as $alias => $fqcn) {
            $importsCode .= "use {$fqcn}".($alias !== class_basename($fqcn) ? " as {$alias}" : '').";\n";
        }

        $attributesCode = $this->renderModelAttributes($attributes);
        $pipelinesCode = $this->renderPipelinesCode($pipelines);
        $logicalName = $baseClass::getName();

        return <<<PHP
<?php
namespace Velm\Models;
use Velm\Core\Models\VelmModelProxy;
{$importsCode}
class {$className} extends VelmModelProxy
{
    protected static string \$logicalName = '{$logicalName}';
{$attributesCode}
{$pipelinesCode}
}
PHP;
    }

    /**
     * @throws \ReflectionException
     */
    protected function classBody(ModelBucket $bucket, string $class): string
    {

        $code = "/* ================ MODEL ATTRIBUTES ================= */\n";

        $code .= $this->renderModelAttributes($bucket);
        $code .= "\n\n";
        $code .= "   /* ================ MODEL METHODS ================= */\n";
        $code .= $this->makePipelinesCode($bucket);

        return <<<PHP
class {$class} extends VelmModelProxy
{
    protected static string \$logicalName = '{$bucket->logicalName}';
    {$code}
}
PHP;
    }

    protected function makeFillableCode(ModelBucket $bucket): string
    {
        $fillable = $this->compileFillable($bucket->definitions);
        if (empty($fillable)) {
            $fillableCode = '';
        } else {
            $fillableCode = $this->emitFillable($fillable);
        }

        return $fillableCode;
    }

    /**
     * @throws \ReflectionException
     */
    protected function makePipelinesCode(ModelBucket $bucket): string
    {
        $pipelines = $this->collectPipelines($bucket);

        return $this->renderPipelinesCode($pipelines);
    }

    /* ================= COMPILE TIME PIPELINES ================ */
    /**
     * Collect pipelines from the model bucket
     *
     * @return array<string, array<array{class: string, method: ReflectionMethod}>> $pipelines
     *
     * @throws \ReflectionException
     */
    protected function collectPipelines(ModelBucket $bucket): array
    {
        $classes = collect($bucket->definitions)->map(fn ($d) => $d->class)->toArray();

        return $this->collectPipelinesForBucket($classes);
    }

    /* ================= COLLECTORS ================= */

    protected function methods(ModelBucket $bucket): array
    {
        $methods = [];

        foreach ($bucket->definitions as $def) {
            $rc = new ReflectionClass($def->class);

            foreach ($rc->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
                if ($m->getDeclaringClass()->getName() !== $def->class) {
                    continue;
                }
                if ($m->isConstructor()) {
                    continue;
                }
                if (str_starts_with($m->getName(), 'scope')) {
                    continue;
                }

                $methods[$m->getName()] ??=
                    $this->methodSignature($m);
            }
        }

        return $methods;
    }

    /**
     * @param  ModelDefinition[]  $definitions
     */
    private function compileFillable(array $definitions): array
    {
        $fillable = [];

        foreach ($definitions as $definition) {
            // Static property
            $instance = new $definition->class;
            $instanceFillable = $instance->getFillable();
            if (filled($instanceFillable)) {
                $fillable = array_merge($fillable, $instanceFillable);
            }
        }

        return array_values(array_unique($fillable));
    }

    /**
     * @return array<string, ReflectionMethod>
     *
     * @throws \ReflectionException
     */
    private function compileRelations(ModelBucket $bucket): array
    {
        $relations = [];
        $definitions = $bucket->definitions;

        foreach ($definitions as $definition) {
            $rc = new ReflectionClass($definition->class);

            foreach ($rc->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
                if ($m->getNumberOfParameters() !== 0) {
                    continue;
                }
                if (! $m->hasReturnType()) {
                    continue;
                }

                $type = (string) $m->getReturnType();

                if (is_subclass_of($type, Relation::class)) {
                    $relations[$m->getName()] = $m;
                }
            }
        }

        return $relations;
    }

    /**
     * Emit $fillable property
     */
    private function emitFillable(array $fillable): string
    {
        $src = <<<'PHP'
protected $fillable = [
PHP;

        foreach ($fillable as $field) {
            $src .= "\n         '".addslashes($field)."',";
        }

        $src .= "\n    ];";

        return $src;

    }

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
        $helpers = app('velm.helpers');
        if (count($lines) > 2) {
            $indentedLines = [];
            foreach ($lines as $index => $line) {
                if ($index === 0) {
                    $indentedLines[] = " $line";
                } elseif ($index === count($lines) - 1) {
                    $indentedLines[] = $helpers->indent($line);
                } else {
                    $indentedLines[] = $helpers->indent($line, 2);
                }
            }
            $export = implode("\n", $indentedLines);
        }

        return <<<PHP
    $signature = {$export};
PHP;
    }

    protected function attributes(ModelBucket $bucket): array
    {
        $attrs = [];

        foreach ($bucket->definitions as $def) {
            $rc = new ReflectionClass($def->class);

            if ($rc->hasProperty('fillable')) {
                $obj = $rc->newInstanceWithoutConstructor();
                foreach ($obj->getFillable() as $f) {
                    $attrs[$f] = true;
                }
            }
        }

        return array_keys($attrs);
    }

    protected function relationships(ModelBucket $bucket): array
    {
        $rels = [];

        foreach ($bucket->definitions as $def) {
            $rc = new ReflectionClass($def->class);

            foreach ($rc->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
                if ($m->getNumberOfParameters() !== 0) {
                    continue;
                }
                if (! $m->hasReturnType()) {
                    continue;
                }

                $type = (string) $m->getReturnType();

                if (is_subclass_of($type, Relation::class)) {
                    $rels[$m->getName()] = $type;
                }
            }
        }

        return $rels;
    }

    protected function scopes(ModelBucket $bucket): array
    {
        $scopes = [];

        foreach ($bucket->definitions as $def) {
            $rc = new ReflectionClass($def->class);

            foreach ($rc->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
                if (! str_starts_with($m->getName(), 'scope')) {
                    continue;
                }

                $params = array_slice($m->getParameters(), 1);

                $paramSig = implode(', ', array_map(
                    fn ($p) => $this->parameterSignature($p),
                    $params
                ));

                $return = $this->returnTypeSignature($m)
                    ?: 'Builder';

                $name = lcfirst(substr($m->getName(), 5));

                $scopes[] = "{$return} {$name}({$paramSig})";
            }
        }

        return $scopes;
    }

    protected function exportDefault(mixed $value): string
    {
        return match (true) {
            is_string($value) => "'".addslashes($value)."'",
            is_bool($value) => $value ? 'true' : 'false',
            is_null($value) => 'null',
            is_array($value) => '[]',
            default => var_export($value, true),
        };
    }

    private function resolveNullableColumns(string $table, string $driver): array
    {
        return match ($driver) {
            'sqlite' => $this->sqliteNullableColumns($table),
            default => $this->informationSchemaNullableColumns($table),
        };
    }

    private function sqliteNullableColumns(string $table): array
    {
        $rows = DB::select("PRAGMA table_info('$table')");

        $nullable = [];

        foreach ($rows as $row) {
            // notnull = 1 means NOT NULL
            $nullable[$row->name] = ((int) $row->notnull) === 0;
        }

        return $nullable;
    }

    private function informationSchemaNullableColumns(string $table): array
    {
        $database = DB::getDatabaseName();

        $rows = DB::select(
            'SELECT COLUMN_NAME, IS_NULLABLE
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [$database, $table]
        );

        $nullable = [];

        foreach ($rows as $row) {
            $nullable[$row->COLUMN_NAME] = $row->IS_NULLABLE === 'YES';
        }

        return $nullable;
    }

    private function mapSchemaTypeToPhp(string $type): string
    {
        return match ($type) {
            'integer', 'bigint', 'smallint' => 'int',
            'float', 'double', 'decimal', 'numeric' => 'float',
            'boolean', 'tinyint' => 'bool',
            'json' => 'array',
            'date', 'datetime', 'timestamp' => '\\Carbon\\Carbon',
            'string', 'text', 'char', 'varchar' => 'string',
            default => 'mixed',
        };
    }

    /* ================= END OF CLASS ================= */

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

    public function isStale(string $proxy): bool
    {
        if (! class_exists($proxy)) {
            return true;
        }

        return velm_compiler()->isStale($proxy);
    }
}
