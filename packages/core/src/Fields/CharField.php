<?php

declare(strict_types=1);

namespace Velm\Fields;

final class CharField extends Field
{
    public function __construct(
        ?string $string = null,
        mixed $default = null,
        bool $required = false,
        bool $readonly = false,
        ?string $column = null,
        public readonly ?int $maxLength = null,
    ) {
        parent::__construct($string, $default, $required, $readonly, $column);
    }

    public static function make(
        ?string $string = null,
        mixed $default = null,
        bool $required = false,
        bool $readonly = false,
        ?int $maxLength = null,
    ): self {
        return new self($string, $default, $required, $readonly, maxLength: $maxLength);
    }

    public function sqlType(): string
    {
        return $this->maxLength !== null
            ? 'VARCHAR('.$this->maxLength.')'
            : 'TEXT';
    }

    public function toPhp(mixed $value): mixed
    {
        return $value === null ? null : (string) $value;
    }
}
