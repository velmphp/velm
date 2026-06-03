<?php

declare(strict_types=1);

namespace Velm\Fields;

final class DatetimeField extends Field
{
    public static function make(
        ?string $string = null,
        mixed $default = null,
        bool $required = false,
        bool $readonly = false,
        ?string $column = null,
    ): static {
        return new self($string, $default, $required, $readonly, $column);
    }

    public function sqlType(): string
    {
        return 'TIMESTAMP';
    }

    public function toPhp(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return (string) $value;
    }

    public function toSql(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return (string) $value;
    }
}
