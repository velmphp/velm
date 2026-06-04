<?php

declare(strict_types=1);

namespace Velm\Views\Authoring;

use Velm\Views\Authoring\Contracts\ViewDeclaration;

final class Field implements ViewDeclaration
{
    /** @var array<string, mixed> */
    private array $options = [];

    private function __construct(
        private readonly string $name,
    ) {}

    public static function make(string $name): self
    {
        return new self($name);
    }

    public function widget(string $widget): self
    {
        $this->options['widget'] = $widget;

        return $this;
    }

    public function toggle(): self
    {
        return $this->widget('toggle');
    }

    public function richText(): self
    {
        return $this->widget('rich_text');
    }

    public function code(string $language = 'json'): self
    {
        $this->options['widget'] = 'code';
        $this->options['code_language'] = $language;

        return $this;
    }

    /** Use another field’s value when this one is empty (e.g. dark logo → light logo). */
    public function whenEmptyUse(string $fieldName): self
    {
        $this->options['when_empty_use'] = $fieldName;

        return $this;
    }

    /**
     * Column span inside the section grid. Use {@see wide()} or the string `full` for a full-width row.
     */
    public function colspan(int|string $colspan): self
    {
        if ($colspan === 'full') {
            return $this->wide();
        }

        if ((int) $colspan < 1) {
            throw new \InvalidArgumentException('colspan must be at least 1.');
        }

        $this->options['colspan'] = (int) $colspan;
        unset($this->options['wide']);

        return $this;
    }

    /** Span all columns in the section grid. */
    public function wide(): self
    {
        $this->options['wide'] = true;
        unset($this->options['colspan']);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['name' => $this->name, ...$this->options];
    }
}
