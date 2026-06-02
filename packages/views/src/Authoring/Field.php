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

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['name' => $this->name, ...$this->options];
    }
}
