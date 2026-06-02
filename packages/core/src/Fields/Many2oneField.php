<?php

declare(strict_types=1);

namespace Velm\Fields;

final class Many2oneField extends Field
{
    public string $comodel = '';

    public static function make(
        string $comodel = '',
        ?string $string = null,
        mixed $default = null,
        bool $required = false,
    ): self {
        $field = new self($string, $default, $required);
        $field->comodel = $comodel;

        return $field;
    }

    public function comodel(string $comodel): self
    {
        $this->comodel = $comodel;

        return $this;
    }

    public function sqlType(): string
    {
        if ($this->comodel === '') {
            throw new \LogicException('Many2oneField requires a comodel.');
        }

        return 'INTEGER';
    }

    public function toPhp(mixed $value): mixed
    {
        return $value === null ? null : (int) $value;
    }
}
