<?php

declare(strict_types=1);

namespace Velm\Models;

use Velm\Fields\CharField;
use Velm\Fields\DatetimeField;
use Velm\Fields\Field;
use Velm\Fields\IntegerField;
use Velm\Recordset\Recordset;
use Velm\Registry;

abstract class Model
{
    protected static ?string $name = null;

    /** @var non-empty-string|null */
    protected static ?string $inherit = null;

    protected static ?string $table = null;

    protected static string $recName = 'name';

    /** When true, {@code created_at} and {@code updated_at} are added and maintained automatically. */
    protected static bool $timestamps = true;

    /**
     * When true, the model supports the mail.thread chatter (messages and followers).
     * Prefer declaring {@see self::$mixins} with {@code mail.thread}; this flag remains
     * as a shorthand for backward compatibility.
     */
    protected static bool $mailThread = false;

    /**
     * Abstract mixin model names composed onto this model (Odoo-style).
     *
     * @var list<string>
     */
    protected static array $mixins = [];

    /** Mixin / abstract models register with {@see Registry::registerMixin()} — no table. */
    protected static bool $abstract = false;

    /** @var array<class-string<static>, static> */
    private static array $behaviorInstances = [];

    /** @var array<class-string<static>, array<string, Field>> */
    private static array $fieldsByClass = [];

    public static function initialize(): void
    {
        $class = static::class;

        if (isset(self::$fieldsByClass[$class])) {
            return;
        }

        $fields = [];

        foreach (static::defineFields() as $fieldName => $field) {
            $fields[$fieldName] = $field->bind($fieldName);
        }

        if (static::isExtension()) {
            self::$fieldsByClass[$class] = $fields;

            return;
        }

        if (static::isAbstract()) {
            self::$fieldsByClass[$class] = $fields;

            return;
        }

        $fields['id'] = IntegerField::make()->label('ID')->readonly()->bind('id');
        $fields['display_name'] = CharField::make()->label('Display Name')->readonly()->bind('display_name');

        if (static::usesTimestamps()) {
            if (! isset($fields['created_at'])) {
                $fields['created_at'] = DatetimeField::make()
                    ->label('Created on')
                    ->readonly()
                    ->bind('created_at');
            }

            if (! isset($fields['updated_at'])) {
                $fields['updated_at'] = DatetimeField::make()
                    ->label('Last updated on')
                    ->readonly()
                    ->bind('updated_at');
            }
        }

        self::$fieldsByClass[$class] = $fields;
    }

    public static function usesTimestamps(): bool
    {
        return static::$timestamps && ! static::isExtension();
    }

    /**
     * Extra domain leaves applied when this model is the target of relational pickers (m2o search).
     *
     * @return list<array{0: string, 1: string, 2: mixed}>
     */
    public static function relationalSearchDomain(): array
    {
        return [];
    }

    public static function usesMailThread(): bool
    {
        if (static::$mailThread) {
            return true;
        }

        return in_array('mail.thread', static::mixins(), true);
    }

    public static function isAbstract(): bool
    {
        return static::$abstract;
    }

    /**
     * @return list<string>
     */
    public static function mixins(): array
    {
        return static::$mixins;
    }

    /**
     * Stateless behavior object for instance methods (one per model class).
     */
    public static function behavior(): static
    {
        $class = static::class;

        if (! isset(self::$behaviorInstances[$class])) {
            self::$behaviorInstances[$class] = new $class;
        }

        return self::$behaviorInstances[$class];
    }

    public static function isExtension(): bool
    {
        if (static::$inherit === null || static::$inherit === '') {
            return false;
        }

        return (new \ReflectionClass(static::class))
            ->getProperty('inherit')
            ->getDeclaringClass()
            ->getName() === static::class;
    }

    /**
     * @return non-empty-string|null
     */
    public static function inherit(): ?string
    {
        return static::$inherit !== null && static::$inherit !== ''
            ? static::$inherit
            : null;
    }

    /**
     * @return array<string, Field>
     */
    public static function defineFields(): array
    {
        return [];
    }

    /**
     * @return array<string, Field>
     */
    public static function fields(): array
    {
        static::initialize();

        $modelName = static::isExtension() ? static::inherit() : static::name();

        if ($modelName !== null && $modelName !== '') {
            try {
                $registry = Registry::active();

                if ($registry->hasFieldSet($modelName)) {
                    return $registry->fieldSet($modelName);
                }
            } catch (\RuntimeException) {
            }
        }

        return self::$fieldsByClass[static::class];
    }

    /**
     * @return array<string, Field>
     */
    public static function baseFields(): array
    {
        static::initialize();

        return self::$fieldsByClass[static::class];
    }

    /**
     * @return array<string, Field>
     */
    public static function extensionFields(): array
    {
        if (! static::isExtension()) {
            throw new \RuntimeException(static::class.' is not a model extension.');
        }

        static::initialize();

        return self::$fieldsByClass[static::class];
    }

    public static function name(): string
    {
        if (static::isExtension()) {
            $inherit = static::inherit();

            if ($inherit === null) {
                throw new \RuntimeException(static::class.' is missing $inherit.');
            }

            return $inherit;
        }

        if (static::$name === null || static::$name === '') {
            throw new \RuntimeException(static::class.' is missing $name.');
        }

        return static::$name;
    }

    public static function table(): string
    {
        if (static::isExtension()) {
            try {
                return Registry::active()->baseModelClass(static::inherit())::table();
            } catch (\RuntimeException) {
            }
        }

        if (static::$table !== null && static::$table !== '') {
            return static::$table;
        }

        return str_replace('.', '_', static::name());
    }

    /**
     * Columns present on the physical table but not owned by Velm field definitions
     * (e.g. Laravel auth columns on {@code users}).
     *
     * @return list<string>
     */
    public static function schemaExternalColumns(): array
    {
        return [];
    }

    /**
     * Whether this class exposes a public instance method callable on a recordset.
     */
    /**
     * Nearest class in the MRO that declares a public static hook (not inherited from Model).
     *
     * @return class-string<Model>|null
     */
    public static function resolveStaticHookClass(Registry $registry, string $modelName, string $method): ?string
    {
        $chain = $registry->extensionChainFor($modelName);

        for ($index = count($chain) - 1; $index >= 0; $index--) {
            $class = $chain[$index];

            if (! method_exists($class, $method)) {
                continue;
            }

            $reflection = new \ReflectionMethod($class, $method);

            if ($reflection->isStatic()
                && $reflection->isPublic()
                && $reflection->getDeclaringClass()->getName() !== Model::class) {
                return $class;
            }
        }

        return null;
    }

    public static function isRecordMethod(string $method): bool
    {
        if (! method_exists(static::class, $method)) {
            return false;
        }

        $reflection = new \ReflectionMethod(static::class, $method);

        if ($reflection->isStatic() || ! $reflection->isPublic()) {
            return false;
        }

        return $reflection->getDeclaringClass()->getName() !== Model::class;
    }

    /**
     * Call the next class in the registry MRO (PyVelm-style super()).
     *
     * Static hooks: {@code static::super(...$args)} — infers the caller method.
     * Instance methods: {@code static::super($recordset, ...$args)} with the recordset first.
     * Legacy: {@code static::super(__FUNCTION__, ...$args)} still works.
     */
    protected static function super(mixed ...$args): mixed
    {
        $frame = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? null;
        $method = $frame['function'] ?? null;

        if (! is_string($method) || $method === '') {
            throw new \LogicException('Could not resolve caller for super().');
        }

        if ($args !== [] && is_string($args[0]) && $args[0] === $method) {
            array_shift($args);
        }

        $callingClass = static::class;
        $wantInstance = $args !== [] && $args[0] instanceof Recordset;
        $parent = static::resolveSuperImplementor($callingClass, $method, $wantInstance);

        if ($parent === null) {
            throw new \LogicException(
                "{$callingClass} has no parent in the model MRO for {$method}().",
            );
        }

        $parentMethod = new \ReflectionMethod($parent, $method);

        if ($parentMethod->isStatic()) {
            return $parent::$method(...$args);
        }

        if ($args === [] || ! $args[0] instanceof Recordset) {
            throw new \LogicException(
                'Instance super() requires a Recordset as the first argument.',
            );
        }

        return $parent::behavior()->{$method}(...$args);
    }

    /**
     * @return class-string<Model>|null
     */
    private static function resolveSuperImplementor(
        string $callingClass,
        string $method,
        bool $wantInstance,
    ): ?string {
        $registry = Registry::active();
        $modelName = $callingClass::isExtension() ? $callingClass::inherit() : $callingClass::name();
        $chain = $registry->extensionChainFor($modelName);
        $index = array_search($callingClass, $chain, true);

        if ($index === false) {
            return null;
        }

        for ($i = $index - 1; $i >= 0; $i--) {
            $class = $chain[$i];

            if (! method_exists($class, $method)) {
                continue;
            }

            $reflection = new \ReflectionMethod($class, $method);

            if ($wantInstance && ! $reflection->isStatic()) {
                return $class;
            }

            if (! $wantInstance && $reflection->isStatic()) {
                return $class;
            }
        }

        return null;
    }

    public static function recNameField(): string
    {
        return static::$recName;
    }

    public static function displayNameFor(array $values): string
    {
        $rec = static::$recName;

        if ($rec !== '' && isset($values[$rec]) && $values[$rec] !== null && $values[$rec] !== '') {
            return (string) $values[$rec];
        }

        if (isset($values['id'])) {
            return (string) $values['id'];
        }

        return static::name();
    }
}
