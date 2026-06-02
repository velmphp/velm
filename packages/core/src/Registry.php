<?php

declare(strict_types=1);

namespace Velm;

use Velm\Models\Model;

final class Registry
{
    private static ?Registry $active = null;

    /** @var array<string, class-string<Model>> */
    private array $models = [];

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
        $registry = new self;
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
        $modelClass::initialize();
        $name = $modelClass::name();

        if (isset($this->models[$name])) {
            throw new \RuntimeException("Model {$name} is already registered.");
        }

        $this->models[$name] = $modelClass;
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
