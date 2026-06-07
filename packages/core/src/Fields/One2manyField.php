<?php

declare(strict_types=1);

namespace Velm\Fields;

use Velm\Models\Model;
use Velm\Registry;

final class One2manyField extends Field
{
    public string $comodel = '';

    public string $inverseName = '';

    public ?string $listView = null;

    public ?string $formView = null;

    public static function make(string $comodel = '', string $inverseName = ''): self
    {
        $field = new self;
        $field->comodel = $comodel;
        $field->inverseName = $inverseName;

        return $field;
    }

    public function comodel(string $comodel): self
    {
        $this->comodel = $comodel;

        return $this;
    }

    public function inverse(string $inverseName): self
    {
        $this->inverseName = $inverseName;

        return $this;
    }

    public function listView(string $listView): self
    {
        $this->listView = $listView;

        return $this;
    }

    public function formView(string $formView): self
    {
        $this->formView = $formView;

        return $this;
    }

    public function sqlType(): string
    {
        throw new \LogicException('One2manyField is not stored as a table column.');
    }

    public function persistsInDatabase(): bool
    {
        return false;
    }

    public function toSql(mixed $value): mixed
    {
        throw new \LogicException(
            'One2many values are written as id lists on the parent; update the inverse Many2one on child records directly.',
        );
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    public function validateInverse(string $modelClass, Registry $registry): void
    {
        if ($this->comodel === '' || $this->inverseName === '') {
            throw new \LogicException(
                "One2manyField {$this->name} on {$modelClass::name()} requires comodel() and inverse().",
            );
        }

        if (! $registry->has($this->comodel)) {
            throw new \RuntimeException(
                "One2many {$modelClass::name()}.{$this->name}: comodel {$this->comodel} is not registered.",
            );
        }

        $comodelClass = $registry->modelClass($this->comodel);
        $inverse = $comodelClass::fields()[$this->inverseName] ?? null;

        if (! $inverse instanceof Many2oneField) {
            throw new \RuntimeException(
                "One2many {$modelClass::name()}.{$this->name}: inverse {$this->inverseName} must be a Many2one on {$this->comodel}.",
            );
        }

        if ($inverse->comodel !== $modelClass::name()) {
            throw new \RuntimeException(
                "One2many {$modelClass::name()}.{$this->name}: {$this->comodel}.{$this->inverseName} must point at {$modelClass::name()}.",
            );
        }
    }
}
