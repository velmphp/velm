<?php

declare(strict_types=1);

namespace Velm\Fields;

abstract class Field
{
    public string $name = '';

    public string $column = '';

    public readonly ?string $string;

    public readonly mixed $default;

    public readonly bool $required;

    public readonly bool $readonly;

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
