<?php

namespace Velm\Core\Concerns;

trait HasLogicalAttributes
{
    protected function initializeHasLogicalAttributes(): void
    {
        velm_utils()->consoleLog("Initializing logical model for logical name {$this->getLogicalName()}...");
        $logicalName = static::$logicalName;

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
            $this->casts = array_merge($this->casts ?? [], $cache['casts']);
        }

        // Appends
        if (! empty($cache['appends'])) {
            $this->appends = array_unique(
                array_merge($this->appends ?? [], $cache['appends'])
            );
        }

        // Custom properties
        foreach ($cache['custom'] as $prop => $value) {
            $this->{$prop} = $value;
        }

        // dump all properties for debugging
        velm_utils()->consoleLog("Resolved properties for logical model {$logicalName}:");
        velm_utils()->consoleLog(collect([
            'fillable' => $this->getFillable(),
            'guarded' => $this->getGuarded(),
            'casts' => $this->casts ?? [],
            'appends' => $this->appends ?? [],
            'custom' => $cache['custom'],
        ]));
    }
}
