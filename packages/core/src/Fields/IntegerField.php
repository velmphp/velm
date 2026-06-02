<?php

declare(strict_types=1);

namespace Velm\Fields;

final class IntegerField extends Field
{
    public static function make(
        ?string $string = null,
        mixed $default = null,
        bool $required = false,
        bool $readonly = false,
    ): self {
        return new self($string, $default, $required, $readonly);
    }

    public function sqlType(): string
    {
        return 'INTEGER';
    }

    public function toPhp(mixed $value): mixed
    {
        return $value === null ? null : (int) $value;
    }
}
