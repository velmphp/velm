<?php

namespace Velm\Core\Runtime;

use Illuminate\Database\Eloquent\Model;

class RelationshipResolver
{
    public static function has(string $model, string $method): bool
    {
        return isset(\Velm::registry()->runtime()::$relationships[$model][$method]);
    }

    public static function call(Model $model, string $method)
    {
        $target = \Velm::registry()->runtime()::$relationships[get_class($model)][$method] ?? null;
        if (! $target) {
            throw new \RuntimeException("Relationship '$method' not found for model '".get_class($model)."'");
        }

        return $model->belongsTo($target); // generic, works for any relation
    }
}
