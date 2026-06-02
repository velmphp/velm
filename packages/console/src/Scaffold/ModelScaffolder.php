<?php

declare(strict_types=1);

namespace Velm\Console\Scaffold;

final class ModelScaffolder
{
    private const STEM_PATTERN = '/^[a-z][a-z0-9_]{0,49}$/';

    /**
     * @return array{path: string, technical: string, class: string, namespace: string}
     */
    public function scaffold(
        string $modelInput,
        string $moduleName,
        string $modulePath,
        bool $force = false,
    ): array {
        [$stem, $technical] = $this->normalizeModel($modelInput, $moduleName);
        $className = $this->classNameFromStem($stem);
        $namespace = ModulePathResolver::modelsNamespace($modulePath, $moduleName);
        $fqn = "{$namespace}\\{$className}";
        $target = $modulePath.'/models/'.$stem.'.php';

        if (is_file($target) && ! $force) {
            throw new \RuntimeException(
                "{$target} already exists — pass --force to overwrite.",
            );
        }

        if (! is_dir($modulePath.'/models') && ! mkdir($modulePath.'/models', 0775, true) && ! is_dir($modulePath.'/models')) {
            throw new \RuntimeException("Could not create models directory under {$modulePath}.");
        }

        $table = str_replace('.', '_', $technical);

        file_put_contents($target, $this->modelContents($namespace, $className, $technical, $table));
        $this->patchManifest($modulePath.'/__velm__.php', $fqn, $className);

        return [
            'path' => $target,
            'technical' => $technical,
            'class' => $className,
            'namespace' => $namespace,
        ];
    }

    /**
     * @return array{0: string, 1: string} stem, technical name
     */
    private function normalizeModel(string $modelInput, string $moduleName): array
    {
        $modelInput = strtolower(trim($modelInput));

        if ($modelInput === '') {
            throw new \InvalidArgumentException('Model name must not be empty.');
        }

        if (! str_contains($modelInput, '.')) {
            if (! preg_match(self::STEM_PATTERN, $modelInput)) {
                throw new \InvalidArgumentException(
                    'Short model name must be snake_case (e.g. product or sale_order).',
                );
            }

            return [$modelInput, "{$moduleName}.{$modelInput}"];
        }

        $parts = explode('.', $modelInput);
        $stem = array_pop($parts);

        if ($stem === null || $stem === '' || ! preg_match(self::STEM_PATTERN, $stem)) {
            throw new \InvalidArgumentException('Model suffix must be snake_case (e.g. inventory.product).');
        }

        if ($parts === [$moduleName] || $parts === []) {
            return [$stem, "{$moduleName}.{$stem}"];
        }

        return [$stem, $modelInput];
    }

    private function classNameFromStem(string $stem): string
    {
        return str_replace('_', '', ucwords($stem, '_'));
    }

    private function modelContents(
        string $namespace,
        string $className,
        string $technical,
        string $table,
    ): string {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Velm\Fields\CharField;
use Velm\Models\Model;

class {$className} extends Model
{
    protected static ?string \$name = '{$technical}';

    protected static ?string \$table = '{$table}';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required(),
        ];
    }
}

PHP;
    }

    private function patchManifest(string $manifestPath, string $fqn, string $shortClass): void
    {
        if (! is_file($manifestPath)) {
            throw new \RuntimeException("Manifest not found: {$manifestPath}");
        }

        $text = file_get_contents($manifestPath);

        if ($text === false) {
            throw new \RuntimeException("Could not read manifest: {$manifestPath}");
        }

        if (! str_contains($text, "use {$fqn};")) {
            $text = preg_replace(
                '/(declare\(strict_types=1\);\s*\n\s*\n)/',
                "$1use {$fqn};\n",
                $text,
                1,
            ) ?? $text;
        }

        if (! str_contains($text, "{$shortClass}::class")) {
            if (preg_match('/->models\s*\(\s*\n/', $text) === 1) {
                $text = preg_replace(
                    '/(->models\s*\(\s*\n(?:.*\n)*?)(\s*\))/s',
                    "$1        {$shortClass}::class,\n$2",
                    $text,
                    1,
                ) ?? $text;
            } elseif (preg_match('/->models\(([^)]*)\)/', $text, $matches) === 1) {
                $inner = trim($matches[1]);
                $replacement = $inner === ''
                    ? "->models({$shortClass}::class)"
                    : "->models({$inner}, {$shortClass}::class)";
                $text = preg_replace('/->models\([^)]*\)/', $replacement, $text, 1) ?? $text;
            } else {
                $text = preg_replace(
                    '/(->depends\([^)]+\))/',
                    "$1\n    ->models({$shortClass}::class)",
                    $text,
                    1,
                ) ?? $text;
            }
        }

        file_put_contents($manifestPath, $text);
    }
}
