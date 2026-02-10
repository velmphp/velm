<?php

namespace Velm\Core\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;
use ReflectionClass;
use Str;
use Velm\Core\Concerns\HasLogicalAttributes;
use Velm\Core\Pipeline\ClassPipelineRuntime;
use Velm\Core\Pipeline\Contracts\Pipelinable;

abstract class LogicalModel extends Model implements Pipelinable
{
    use HasLogicalAttributes;

    protected static string $logicalName;

    protected static array $velmPropertyCache = [];

    /* -------------------------------------------------
     | Boot hook — aggregate properties ONCE
     |-------------------------------------------------*/

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        static::resolveVelmProperties();
    }

    protected static function resolveVelmProperties(): void
    {
        $logicalName = static::$logicalName;

        if (isset(static::$velmPropertyCache[$logicalName])) {
            return;
        }

        $extensions = velm()->registry()->pipeline()->find($logicalName);
        if (empty($extensions)) {
            return;
        }

        $cache = [
            'fillable' => [],
            'guarded' => [],
            'casts' => [],
            'appends' => [],
            'table' => null,
            'connection' => null,
            'custom' => [],
        ];

        $hasFillable = false;

        foreach ($extensions as $ext) {
            // For protected properties, use reflection to check if they are declared on the extension class itself
            $reflection = new ReflectionClass($ext);

            if (self::isDeclaredOn($ext, 'fillable')) {
                $hasFillable = true;
                // get fillabe from reflection
                $fillable = $ext->getFillable() ?? [];
                $cache['fillable'] = array_merge($cache['fillable'], $fillable ?? []);
            }

            if (self::isDeclaredOn($ext, 'guarded')) {
                $guarded = $ext->getGuarded() ?? [];
                $cache['guarded'] = array_merge($cache['guarded'], $guarded ?? []);
            }

            if (self::isDeclaredOn($ext, 'casts')) {
                $casts = $ext->getCasts() ?? [];
                $cache['casts'] = array_merge($cache['casts'], $casts ?? []);
            }

            if (self::isDeclaredOn($ext, 'appends')) {
                $appends = $ext->getAppends() ?? [];
                $cache['appends'] = array_merge($cache['appends'], $appends ?? []);
            }

            foreach (get_object_vars($ext) as $prop => $value) {
                if (! property_exists(self::class, $prop)) {
                    $cache['custom'][$prop] = $value;
                }
            }
        }

        if ($hasFillable) {
            $cache['fillable'] = array_values(array_unique($cache['fillable']));
            $cache['guarded'] = []; // fillable-mode
        } elseif (! empty($cache['guarded'])) {
            $cache['guarded'] = array_values(array_unique($cache['guarded']));
        }

        static::$velmPropertyCache[$logicalName] = $cache;
    }

    /**
     * @throws \ReflectionException
     */
    private static function isDeclaredOn(object $object, string $property): bool
    {
        $classRef = new ReflectionClass($object);
        if (! $classRef->hasProperty($property)) {
            return false;
        }
        $ref = new \ReflectionProperty($object, $property);

        return $ref->getDeclaringClass()->getName() === get_class($object);
    }

    /**
     * Override table resolution to use physical model
     */
    public function getTable()
    {
        if (isset($this->table)) {
            return $this->table;
        }
        $first = $this->getExtensions()[0] ?? null;

        return $first
            ? $first->getTable()
            : parent::getTable();
    }

    public function getExtensions(): array
    {
        return velm()->registry()->pipeline()->find(static::$logicalName) ?? [];
    }

    /**
     * Return logical name
     */
    public function getLogicalName(): string
    {
        if (! static::$logicalName) {
            throw new LogicException(
                'Logical model used without logical name. '.
                'Use velm_model("Product") or Model::make().'
            );
        }

        return static::$logicalName;
    }

    /* ---------------------------------
     | Instance calls → ALWAYS pipeline
     *---------------------------------*/
    public function __call($method, $parameters)
    {
        if (ClassPipelineRuntime::hasInstancePipeline($logicalName = $this->getLogicalName(), $method)) {

            return ClassPipelineRuntime::call($this, $method, $parameters);
        }

        // 2️⃣ Scope resolution (query builder path)
        if (
            ClassPipelineRuntime::hasScope($logicalName, $method)
        ) {
            return ClassPipelineRuntime::callScope(
                $this,
                $method,
                $parameters
            );
        }

        return parent::__call($method, $parameters);
    }

    /* ---------------------------------
     | Static calls → forbidden
     *---------------------------------*/
    public static function __callStatic($method, $parameters)
    {
        throw new LogicException(
            'Static calls are not supported on Velm logical models. '.
            'Use velm_model()->method() instead.'
        );
    }

    public function hasGetMutator($key)
    {
        $method = 'get'.\Str::studly($key).'Attribute';

        // Pipeline accessor?
        if (ClassPipelineRuntime::hasInstancePipeline(
            $this->getLogicalName(),
            $method
        )) {
            return true;
        }

        return parent::hasGetMutator($key);
    }

    public function getAttributeValue($key)
    {
        $method = 'get'.\Str::studly($key).'Attribute';

        if (ClassPipelineRuntime::hasInstancePipeline(
            $this->getLogicalName(),
            $method
        )) {
            // Call pipeline accessor
            return ClassPipelineRuntime::call(
                $this,
                $method,
                []
            );
        }

        return parent::getAttributeValue($key);
    }

    public function hasSetMutator($key)
    {
        $method = 'set'.Str::studly($key).'Attribute';

        if (ClassPipelineRuntime::hasInstancePipeline(
            $this->getLogicalName(),
            $method
        )) {
            return true;
        }

        return parent::hasSetMutator($key);
    }

    public function setAttribute($key, $value)
    {
        $method = 'set'.Str::studly($key).'Attribute';

        if (ClassPipelineRuntime::hasInstancePipeline(
            $this->getLogicalName(),
            $method
        )) {
            ClassPipelineRuntime::call(
                $this,
                $method,
                [$value]
            );

            return $this;
        }

        return parent::setAttribute($key, $value);
    }
}
