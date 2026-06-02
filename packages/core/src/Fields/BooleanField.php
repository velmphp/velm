<?php

declare(strict_types=1);

namespace Velm\Fields;

final class BooleanField extends Field
{
    public static function make(
        ?string $string = null,
        mixed $default = false,
        bool $required = false,
    ): self {
        return new self($string, $default, $required);
    }

    public function sqlType(): string
    {
        return 'INTEGER';
    }

    public function toPhp(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return (bool) $value;
    }

    public function toSql(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return $value ? 1 : 0;
    }
}
