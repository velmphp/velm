<?php

declare(strict_types=1);

namespace Velm\Migrations;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition as BlueprintColumnDefinition;
use Velm\Migrations\ColumnDefinition;

final class MigrationColumnBlueprint
{
    public static function addColumn(Blueprint $blueprint, ColumnDefinition $column): void
    {
        $definition = self::defineColumn($blueprint, $column);

        if ($column->nullable) {
            $definition->nullable();
        }

        if ($column->default !== null) {
            $definition->default($column->default);
        }
    }

    private static function defineColumn(Blueprint $blueprint, ColumnDefinition $column): BlueprintColumnDefinition
    {
        return match (strtoupper($column->sqlType)) {
            'TEXT' => $blueprint->text($column->name),
            'INTEGER' => $blueprint->integer($column->name),
            'BOOLEAN' => $blueprint->integer($column->name),
            default => preg_match('/^VARCHAR\((\d+)\)$/i', $column->sqlType, $matches) === 1
                ? $blueprint->string($column->name, (int) $matches[1])
                : throw new \InvalidArgumentException("Unsupported migration column type {$column->sqlType}."),
        };
    }
}
