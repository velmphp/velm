<?php

declare(strict_types=1);

namespace Velm\Fields;

final class Many2oneField extends Field
{
    public function __construct(
        public readonly string $comodel,
        ?string $string = null,
        mixed $default = null,
        bool $required = false,
        ?string $column = null,
    ) {
        parent::__construct($string, $default, $required, false, $column);
    }

    public static function make(
        string $comodel,
        ?string $string = null,
        mixed $default = null,
        bool $required = false,
    ): self {
        return new self($comodel, $string, $default, $required);
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
