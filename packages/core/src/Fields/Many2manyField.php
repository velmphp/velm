<?php

declare(strict_types=1);

namespace Velm\Fields;

use Velm\Models\Model;
use Velm\Registry;

final class Many2manyField extends Field
{
    public string $comodel = '';

    public ?string $relation = null;

    public ?string $column1 = null;

    public ?string $column2 = null;

    public static function make(string $comodel = ''): self
    {
        $field = new self;
        $field->comodel = $comodel;

        return $field;
    }

    public function comodel(string $comodel): self
    {
        $this->comodel = $comodel;

        return $this;
    }

    public function relation(string $relation, ?string $column1 = null, ?string $column2 = null): self
    {
        $this->relation = $relation;
        $this->column1 = $column1;
        $this->column2 = $column2;

        return $this;
    }

    public function sqlType(): string
    {
        throw new \LogicException('Many2manyField is not stored as a table column.');
    }

    public function persistsInDatabase(): bool
    {
        return false;
    }

    public function toSql(mixed $value): mixed
    {
        throw new \LogicException('Many2manyField values are written via create/write as id lists.');
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return array{0: string, 1: string, 2: string}
     */
    public function resolveSpec(string $modelClass, Registry $registry): array
    {
        if ($this->comodel === '') {
            throw new \LogicException('Many2manyField requires a comodel.');
        }

        $thisTable = $modelClass::table();
        $otherTable = $registry->modelClass($this->comodel)::table();

        if ($this->comodel === $modelClass::name()) {
            if ($this->relation === null || $this->column1 === null || $this->column2 === null) {
                throw new \LogicException(
                    "Self-referential Many2many on {$modelClass::name()} requires explicit relation/columns.",
                );
            }

            return [$this->relation, $this->column1, $this->column2];
        }

        $relation = $this->relation ?? implode('_', [min($thisTable, $otherTable), max($thisTable, $otherTable)]).'_rel';
        $col1 = $this->column1 ?? "{$thisTable}_id";
        $col2 = $this->column2 ?? "{$otherTable}_id";

        return [$relation, $col1, $col2];
    }
}
