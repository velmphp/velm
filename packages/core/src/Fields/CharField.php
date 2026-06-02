<?php

declare(strict_types=1);

namespace Velm\Fields;

final class CharField extends Field
{
    public ?int $maxLength = null;

    public static function make(
        ?string $string = null,
        mixed $default = null,
        bool $required = false,
        bool $readonly = false,
        ?int $maxLength = null,
    ): self {
        $field = new self($string, $default, $required, $readonly);

        if ($maxLength !== null) {
            $field->maxLength = $maxLength;
        }

        return $field;
    }

    public function maxLength(?int $maxLength): self
    {
        $this->maxLength = $maxLength;

        return $this;
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
