<?php

declare(strict_types=1);

namespace Velm\Models;

use Velm\Fields\CharField;
use Velm\Fields\Field;
use Velm\Fields\IntegerField;

abstract class Model
{
    protected static ?string $name = null;

    /** @var non-empty-string|null */
    protected static ?string $inherit = null;

    protected static ?string $table = null;

    protected static string $recName = 'name';

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

        $fields['id'] = IntegerField::make()->label('ID')->readonly()->bind('id');
        $fields['display_name'] = CharField::make()->label('Display Name')->readonly()->bind('display_name');

        self::$fieldsByClass[$class] = $fields;
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
                $registry = \Velm\Registry::active();

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
        if (static::$table !== null && static::$table !== '') {
            return static::$table;
        }

        return str_replace('.', '_', static::name());
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
