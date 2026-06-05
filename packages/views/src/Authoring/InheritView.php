<?php

declare(strict_types=1);

namespace Velm\Views\Authoring;

use Velm\Views\Authoring\Contracts\ViewDeclaration;

/**
 * Fluent builder for VIEW_INHERITS (patches applied to a parent view arch).
 *
 * @example
 * InheritView::make('partner.form.ext')
 *     ->extends('partners.partner.form')
 *     ->setCols(2)
 *     ->updateSection('identity', title: 'Contact', cols: 2)
 *     ->afterField('identity', 'name', Field::make('website'))
 *     ->removeSection('organization', 'address')
 *     ->afterSection('identity', Section::make('location', 'Location')->cols(2)->fields('company_id', 'country_id'));
 */
final class InheritView implements ViewDeclaration
{
    private string $inherit = '';

    private int $priority = 20;

    /** @var list<array<string, mixed>> */
    private array $operations = [];

    private function __construct(
        private readonly string $name,
    ) {}

    public static function make(string $name): self
    {
        if ($name === '') {
            throw new \InvalidArgumentException('Inherit view name must not be empty.');
        }

        return new self($name);
    }

    public function extends(string $inherit): self
    {
        if ($inherit === '') {
            throw new \InvalidArgumentException('Inherit view parent ref must not be empty.');
        }

        $this->inherit = $inherit;

        return $this;
    }

    /** Alias of {@see extends()}. */
    public function inherit(string $inherit): self
    {
        return $this->extends($inherit);
    }

    public function priority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * @param  list<array<string, mixed>>  $operations
     */
    public function operations(array $operations): self
    {
        foreach ($operations as $operation) {
            if (! is_array($operation)) {
                throw new \InvalidArgumentException('Each inherit operation must be an array.');
            }

            $this->appendOp($operation);
        }

        return $this;
    }

    public function setCols(int $cols): self
    {
        return $this->set(ViewTarget::cols(), $cols);
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    public function updateSection(
        string $name,
        ?string $title = null,
        ?int $cols = null,
        array $attrs = [],
    ): self {
        $merged = $attrs;

        if ($title !== null) {
            $merged['title'] = $title;
        }

        if ($cols !== null) {
            $merged['cols'] = $cols;
        }

        return $this->update(ViewTarget::section($name), $merged);
    }

    public function removeSection(string ...$names): self
    {
        foreach ($names as $name) {
            $this->remove(ViewTarget::section($name));
        }

        return $this;
    }

    public function afterField(string $section, string $field, mixed $value): self
    {
        return $this->after(ViewTarget::section($section)->field($field), $value);
    }

    public function beforeField(string $section, string $field, mixed $value): self
    {
        return $this->before(ViewTarget::section($section)->field($field), $value);
    }

    public function afterSection(string $section, Section|array $value): self
    {
        return $this->after(ViewTarget::section($section), $value);
    }

    public function beforeSection(string $section, Section|array $value): self
    {
        return $this->before(ViewTarget::section($section), $value);
    }

    public function appendInSection(string $section, mixed ...$fields): self
    {
        foreach ($fields as $field) {
            $this->appendOp(ViewOp::append(
                ViewTarget::section($section)->fields()->segments(),
                $field,
            ));
        }

        return $this;
    }

    public function prependInSection(string $section, mixed ...$fields): self
    {
        foreach (array_reverse($fields) as $field) {
            $this->appendOp(ViewOp::prepend(
                ViewTarget::section($section)->fields()->segments(),
                $field,
            ));
        }

        return $this;
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    public function updateColumn(
        string $name,
        ?string $label = null,
        array $attrs = [],
    ): self {
        $merged = $attrs;

        if ($label !== null) {
            $merged['label'] = $label;
        }

        return $this->update(ViewTarget::column($name), $merged);
    }

    public function removeColumn(string ...$names): self
    {
        foreach ($names as $name) {
            $this->remove(ViewTarget::column($name));
        }

        return $this;
    }

    /**
     * @param  string|array<mixed>|ViewTarget  $target
     */
    public function remove(string|array|ViewTarget $target): self
    {
        return $this->appendOp(ViewOp::remove($this->normalizeTarget($target)));
    }

    /**
     * @param  string|array<mixed>|ViewTarget  $target
     */
    public function set(string|array|ViewTarget $target, mixed $value): self
    {
        return $this->appendOp(ViewOp::set($this->normalizeTarget($target), $value));
    }

    /**
     * @param  string|array<mixed>|ViewTarget  $target
     */
    public function replace(string|array|ViewTarget $target, mixed $value): self
    {
        return $this->appendOp(ViewOp::replace($this->normalizeTarget($target), $value));
    }

    /**
     * @param  string|array<mixed>|ViewTarget  $target
     * @param  array<string, mixed>  $attrs
     */
    public function update(string|array|ViewTarget $target, array $attrs): self
    {
        return $this->appendOp(ViewOp::update($this->normalizeTarget($target), $attrs));
    }

    /**
     * @param  string|array<mixed>|ViewTarget  $target
     */
    public function after(string|array|ViewTarget $target, mixed $value): self
    {
        return $this->appendOp(ViewOp::after($this->normalizeTarget($target), $value));
    }

    /**
     * @param  string|array<mixed>|ViewTarget  $target
     */
    public function before(string|array|ViewTarget $target, mixed $value): self
    {
        return $this->appendOp(ViewOp::before($this->normalizeTarget($target), $value));
    }

    /**
     * @param  string|array<mixed>|ViewTarget  $target
     */
    public function append(string|array|ViewTarget $target, mixed $value): self
    {
        return $this->appendOp(ViewOp::append($this->normalizeTarget($target), $value));
    }

    /**
     * @param  string|array<mixed>|ViewTarget  $target
     */
    public function prepend(string|array|ViewTarget $target, mixed $value): self
    {
        return $this->appendOp(ViewOp::prepend($this->normalizeTarget($target), $value));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if ($this->inherit === '') {
            throw new \LogicException("Inherit view {$this->name} is missing extends().");
        }

        return [
            'name' => $this->name,
            'inherit' => $this->inherit,
            'operations' => $this->operations,
            'priority' => $this->priority,
        ];
    }

    /**
     * @param  array<string, mixed>  $operation
     */
    private function appendOp(array $operation): self
    {
        if (isset($operation['value'])) {
            $operation['value'] = $this->normalizeValue($operation['value']);
        }

        $this->operations[] = $operation;

        return $this;
    }

    /**
     * @param  string|array<mixed>|ViewTarget  $target
     * @return list<mixed>
     */
    private function normalizeTarget(string|array|ViewTarget $target): array
    {
        if ($target instanceof ViewTarget) {
            return $target->segments();
        }

        if (is_string($target)) {
            return ViewTarget::path($target)->segments();
        }

        return array_values($target);
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof ViewDeclaration) {
            return $value->toArray();
        }

        if (is_string($value)) {
            return ['name' => $value];
        }

        return $value;
    }
}
