<?php

declare(strict_types=1);

namespace Velm\Fields;

abstract class Field
{
    public string $name = '';

    public string $column = '';

    public ?string $string = null;

    public mixed $default = null;

    public bool $required = false;

    public bool $readonly = false;

    public function __construct(
        ?string $string = null,
        mixed $default = null,
        bool $required = false,
        bool $readonly = false,
        ?string $column = null,
    ) {
        $this->string = $string;
        $this->default = $default;
        $this->required = $required;
        $this->readonly = $readonly;

        if ($column !== null && $column !== '') {
            $this->column = $column;
        }
    }

    public function label(?string $string): static
    {
        $this->string = $string;

        return $this;
    }

    public function displayLabel(): string
    {
        if ($this->string !== null && $this->string !== '') {
            return $this->string;
        }

        if ($this->name !== '') {
            return self::humanizeFieldName($this->name);
        }

        return '';
    }

    public static function humanizeFieldName(string $name): string
    {
        $base = str_ends_with($name, '_id')
            ? substr($name, 0, -3)
            : $name;

        return ucwords(str_replace('_', ' ', $base));
    }

    public function default(mixed $default): static
    {
        $this->default = $default;

        return $this;
    }

    public function required(bool $required = true): static
    {
        $this->required = $required;

        return $this;
    }

    public function readonly(bool $readonly = true): static
    {
        $this->readonly = $readonly;

        return $this;
    }

    public function column(?string $column): static
    {
        if ($column !== null && $column !== '') {
            $this->column = $column;
        }

        return $this;
    }

    public function bind(string $name): static
    {
        $field = clone $this;
        $field->name = $name;
        $field->column = $this->column !== '' ? $this->column : $name;

        return $field;
    }

    abstract public function sqlType(): string;

    public function toPhp(mixed $value): mixed
    {
        return $value;
    }

    public function toSql(mixed $value): mixed
    {
        return $value;
    }
}
