<?php

namespace Velm\Core\Concerns;

use Velm\Core\Pipeline\ClassPipelineRuntime;

trait HasLogicalAttributes
{
    protected array $velmMeta = [];

    protected function initializeHasLogicalAttributes(): void
    {
        $logicalName = $this->getLogicalName();

        $cache = static::$velmPropertyCache[$logicalName] ?? null;
        if (! $cache) {
            return;
        }

        // Fillable / Guarded
        if (! empty($cache['fillable'])) {
            // Set fillable
            $this->mergeFillable($cache['fillable']);
            $this->guard([]); // critical
        } elseif ($cache['guarded'] !== null) {
            $this->guard($cache['guarded']);
        }

        // Casts
        if (! empty($cache['casts'])) {
            $this->casts = array_merge($this->casts, $cache['casts']);
        }

        // Appends
        if (! empty($cache['appends'])) {
            $this->appends = array_unique(
                array_merge($this->appends, $cache['appends'])
            );
        }

        // Custom properties will be handled via __get and __set magic methods, so we just store them in the cache for now
        if (! empty($cache['custom'])) {
            $this->mergeMeta($cache['custom']);
        }
    }

    protected function mergeMeta(array $custom): void
    {
        $this->velmMeta = array_merge($this->velmMeta ?? [], $custom);
    }

    public function getAllMeta(): array
    {
        return $this->velmMeta ?? [];
    }

    public function getMeta(string $key, $default = null)
    {
        return $this->velmMeta[$key] ?? $default;
    }

    public function __get($key)
    {
        if (array_key_exists($key, $this->getAllMeta())) {
            return $this->getMeta($key);
        }

        // Already loaded
        if ($this->relationLoaded($key)) {
            return $this->relations[$key];
        }

        // Pretend the method exists
        if (ClassPipelineRuntime::hasInstancePipeline(
            static::class,
            $key
        )) {
            // Laravel normally calls: $this->$key()
            // We forward that call — pipeline handles it
            $relation = $this->$key();

            if ($relation instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                $this->setRelation($key, $results = $relation->getResults());

                return $results;
            }
        }

        return parent::__get($key);
    }

    public function __set($key, $value)
    {
        if (array_key_exists($key, $this->getAllMeta())) {
            $this->mergeMeta([$key => $value]);
        } else {
            parent::__set($key, $value);
        }
    }
}
