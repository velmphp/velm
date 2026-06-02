<?php

declare(strict_types=1);

namespace Velm\Fields;

final class TextField extends Field
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
        return 'TEXT';
    }

    public function toPhp(mixed $value): mixed
    {
        return $value === null ? null : (string) $value;
    }
}
