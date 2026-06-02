<?php

declare(strict_types=1);

namespace Velm\Views\Authoring;

use Velm\Views\Authoring\Contracts\MenuDeclaration;

final class MenuGroup implements MenuDeclaration
{
    private ?string $parent = null;

    private int $sequence = 10;

    private ?string $icon = null;

    private function __construct(
        private readonly string $name,
        private readonly string $label,
    ) {}

    public static function make(string $name, string $label): self
    {
        return new self($name, $label);
    }

    public function parentRef(string $moduleDotName): self
    {
        $this->parent = $moduleDotName;

        return $this;
    }

    public function sequence(int $sequence): self
    {
        $this->sequence = $sequence;

        return $this;
    }

    public function icon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $row = [
            'name' => $this->name,
            'label' => $this->label,
            'sequence' => $this->sequence,
        ];

        if ($this->parent !== null) {
            $row['parent'] = $this->parent;
        }

        if ($this->icon !== null) {
            $row['icon'] = $this->icon;
        }

        return $row;
    }
}
