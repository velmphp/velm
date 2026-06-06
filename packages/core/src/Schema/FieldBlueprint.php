<?php

declare(strict_types=1);

namespace Velm\Schema;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Velm\Fields\BooleanField;
use Velm\Fields\CharField;
use Velm\Fields\DatetimeField;
use Velm\Fields\Field;
use Velm\Fields\IntegerField;
use Velm\Fields\Many2oneField;
use Velm\Fields\TextField;

final class FieldBlueprint
{
    public static function addColumn(Blueprint $blueprint, Field $field): void
    {
        $column = self::defineColumn($blueprint, $field);

        if (! $field->required) {
            $column->nullable();
        }

        if ($field->default !== null) {
            $column->default($field->toSql($field->default));
        }
    }

    public static function defineColumn(Blueprint $blueprint, Field $field): ColumnDefinition
    {
        $name = $field->column;

        if ($field instanceof CharField) {
            return $field->maxLength !== null
                ? $blueprint->string($name, $field->maxLength)
                : $blueprint->text($name);
        }

        if ($field instanceof TextField) {
            return $blueprint->text($name);
        }

        if ($field instanceof IntegerField || $field instanceof BooleanField || $field instanceof Many2oneField) {
            return $blueprint->integer($name);
        }

        if ($field instanceof DatetimeField) {
            return $blueprint->timestamp($name);
        }

        throw new \InvalidArgumentException(
            'Field '.$field->name.' ('.$field::class.') cannot be mapped to a schema column.',
        );
    }
}
