<?php

declare(strict_types=1);

namespace Velm;

use Velm\Fields\Field;
use Velm\Models\Model;

final class Registry
{
    private static ?Registry $active = null;

    /** @var array<string, class-string<Model>> */
    private array $models = [];

    /** @var array<string, array<string, Field>> */
    private array $fieldSets = [];

    /** @var array<string, list<class-string<Model>>> */
    private array $extensions = [];

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

        $modelClass::initialize();
        $name = $modelClass::name();

        if (isset($this->models[$name])) {
            throw new \RuntimeException("Model {$name} is already registered.");
        }

        $this->models[$name] = $modelClass;
        $this->fieldSets[$name] = $modelClass::baseFields();
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
        $current = $this->fieldSets[$inheritName] ?? $this->models[$inheritName]::baseFields();
        $this->fieldSets[$inheritName] = $this->mergeFields($current, $extensionClass::extensionFields());
        $this->extensions[$inheritName][] = $extensionClass;
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

    /**
     * @return list<class-string<Model>>
     */
    public function extensionsFor(string $name): array
    {
        return $this->extensions[$name] ?? [];
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
}
