<?php

declare(strict_types=1);

namespace Velm;

use Velm\Computed\ComputedFieldGraph;
use Velm\Fields\Field;
use Velm\Fields\One2manyField;
use Velm\Models\Model;

final class Registry
{
    private static ?Registry $active = null;

    /** @var array<string, class-string<Model>> */
    private array $models = [];

    /** @var array<string, array<string, Field>> */
    private array $fieldSets = [];

    /**
     * MRO chain per model name: base class first, each extension appended in load order.
     *
     * @var array<string, list<class-string<Model>>>
     */
    private array $extensionChain = [];

    private ?ComputedFieldGraph $computedGraph = null;

    /** @var array<string, class-string<Model>> */
    private array $mixins = [];

    public static function active(): self
    {
        if (self::$active === null) {
            throw new \RuntimeException('No active Velm registry. Use Registry::using().');
        }

        return self::$active;
    }

    /**
     * @template TReturn
     *
     * @param  callable(self): TReturn  $callback
     * @return TReturn
     */
    public static function using(callable $callback): mixed
    {
        return self::with(new self, $callback);
    }

    /**
     * @template TReturn
     *
     * @param  callable(self): TReturn  $callback
     * @return TReturn
     */
    public static function with(self $registry, callable $callback): mixed
    {
        $previous = self::$active;
        self::$active = $registry;

        try {
            return $callback($registry);
        } finally {
            self::$active = $previous;
        }
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    public function register(string $modelClass): void
    {
        if ($modelClass::isExtension()) {
            throw new \RuntimeException("{$modelClass} is a model extension; use registerExtension().");
        }

        if ($modelClass::isAbstract()) {
            throw new \RuntimeException("{$modelClass} is abstract; use registerMixin().");
        }

        $modelClass::initialize();
        $name = $modelClass::name();

        if (isset($this->models[$name])) {
            throw new \RuntimeException("Model {$name} is already registered.");
        }

        $this->models[$name] = $modelClass;
        $this->fieldSets[$name] = $modelClass::baseFields();
        $this->extensionChain[$name] = [$modelClass];

        foreach ($this->fieldSets[$name] as $field) {
            if ($field instanceof One2manyField) {
                $field->validateInverse($modelClass, $this);
            }
        }

        $this->rebuildComputedGraph();
    }

    /**
     * @param  class-string<Model>  $extensionClass
     */
    public function registerExtension(string $extensionClass): void
    {
        if (! $extensionClass::isExtension()) {
            throw new \RuntimeException("{$extensionClass} must set \$inherit without \$name.");
        }

        $inheritName = $extensionClass::inherit();

        if ($inheritName === null) {
            throw new \RuntimeException("{$extensionClass} is missing \$inherit.");
        }

        if (! isset($this->models[$inheritName])) {
            throw new \RuntimeException(
                "Cannot extend {$inheritName}: model is not registered. Load its module first.",
            );
        }

        $extensionClass::initialize();
        $current = $this->fieldSets[$inheritName] ?? $this->baseModelClass($inheritName)::baseFields();
        $this->fieldSets[$inheritName] = $this->mergeFields($current, $extensionClass::extensionFields());
        $this->extensionChain[$inheritName][] = $extensionClass;
        $this->models[$inheritName] = $extensionClass;

        $this->rebuildComputedGraph();
    }

    /**
     * @param  class-string<Model>  $class
     * @return class-string<Model>|null
     */
    public function superClass(string $class): ?string
    {
        $modelName = $class::isExtension() ? $class::inherit() : $class::name();
        $chain = $this->extensionChain[$modelName] ?? [];
        $index = array_search($class, $chain, true);

        if ($index === false || $index === 0) {
            return null;
        }

        return $chain[$index - 1];
    }

    /**
     * @return class-string<Model>
     */
    public function baseModelClass(string $modelName): string
    {
        $chain = $this->extensionChain[$modelName] ?? [];

        if ($chain === []) {
            throw new \InvalidArgumentException("Unknown model {$modelName}.");
        }

        return $chain[0];
    }

    public function hasFieldSet(string $name): bool
    {
        return isset($this->fieldSets[$name]);
    }

    /**
     * @return array<string, Field>
     */
    public function fieldSet(string $name): array
    {
        if (! isset($this->fieldSets[$name])) {
            throw new \InvalidArgumentException("No field set for model {$name}.");
        }

        return $this->fieldSets[$name];
    }

    public function field(string $modelName, string $fieldName): ?Field
    {
        if (! isset($this->fieldSets[$modelName])) {
            return null;
        }

        return $this->fieldSets[$modelName][$fieldName] ?? null;
    }

    /**
     * @return array<string, Field>
     */
    public function fieldsFor(string $modelName): array
    {
        if (isset($this->fieldSets[$modelName])) {
            return $this->fieldSets[$modelName];
        }

        if (! isset($this->models[$modelName])) {
            return [];
        }

        return $this->modelClass($modelName)::fields();
    }

    /**
     * @return list<class-string<Model>>
     */
    public function extensionsFor(string $name): array
    {
        $chain = $this->extensionChain[$name] ?? [];

        if ($chain === []) {
            return [];
        }

        return array_slice($chain, 1);
    }

    /**
     * @return list<class-string<Model>>
     */
    public function extensionChainFor(string $name): array
    {
        return $this->extensionChain[$name] ?? [];
    }

    /**
     * @param  array<string, Field>  $base
     * @param  array<string, Field>  $extra
     * @return array<string, Field>
     */
    private function mergeFields(array $base, array $extra): array
    {
        $merged = [];

        foreach ($base as $name => $field) {
            $merged[$name] = $field->bind($name);
        }

        foreach ($extra as $name => $field) {
            $merged[$name] = $field->bind($name);
        }

        return $merged;
    }

    public function has(string $name): bool
    {
        return isset($this->models[$name]);
    }

    /**
     * Effective class for ORM dispatch (top of the extension chain).
     *
     * @return class-string<Model>
     */
    public function modelClass(string $name): string
    {
        if (! isset($this->models[$name])) {
            throw new \InvalidArgumentException("Unknown model {$name}.");
        }

        return $this->models[$name];
    }

    /**
     * @return array<string, class-string<Model>>
     */
    public function models(): array
    {
        return $this->models;
    }

    public function computedGraph(): ComputedFieldGraph
    {
        return $this->computedGraph ?? ComputedFieldGraph::empty();
    }

    /**
     * @param  class-string<Model>  $mixinClass
     */
    public function registerMixin(string $mixinClass): void
    {
        if (! $mixinClass::isAbstract()) {
            throw new \RuntimeException("{$mixinClass} must set \$abstract = true for mixin registration.");
        }

        $mixinClass::initialize();
        $name = $mixinClass::name();

        if (isset($this->mixins[$name])) {
            throw new \RuntimeException("Mixin {$name} is already registered.");
        }

        $this->mixins[$name] = $mixinClass;
    }

    public function hasMixin(string $modelName, string $mixinName): bool
    {
        foreach ($this->extensionChainFor($modelName) as $class) {
            if (in_array($mixinName, $class::mixins(), true)) {
                return true;
            }

            if ($mixinName === 'mail.thread' && self::classDeclaresMailThread($class)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  class-string<Model>  $class
     */
    private static function classDeclaresMailThread(string $class): bool
    {
        $reflection = new \ReflectionClass($class);

        if (! $reflection->hasProperty('mailThread')) {
            return false;
        }

        $property = $reflection->getProperty('mailThread');

        if ($property->getDeclaringClass()->getName() !== $class) {
            return false;
        }

        return (bool) $property->getValue();
    }

    private function rebuildComputedGraph(): void
    {
        $this->computedGraph = ComputedFieldGraph::build($this);
    }
}
