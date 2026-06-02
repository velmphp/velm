<?php

declare(strict_types=1);

namespace Velm\Views\Authoring;

use Velm\Views\Authoring\Contracts\MenuDeclaration;

final class MenuItem implements MenuDeclaration
{
    private ?string $parent = null;

    private ?string $href = null;

    private ?string $view = null;

    private ?string $viewModule = null;

    private int $sequence = 10;

    private ?string $icon = null;

    private function __construct(
        private readonly string $name,
        private readonly string $label,
        private readonly string $module,
    ) {}

    public static function make(string $module, string $name, string $label): self
    {
        return new self($name, $label, $module);
    }

    public function parent(string $parentModule, string $parentName): self
    {
        $this->parent = "{$parentModule}.{$parentName}";

        return $this;
    }

    public function parentRef(string $moduleDotName): self
    {
        $this->parent = $moduleDotName;

        return $this;
    }

    public function href(string $href): self
    {
        $this->href = $href;

        return $this;
    }

    public function view(string $view, ?string $module = null): self
    {
        $this->view = $view;
        $this->viewModule = $module;

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
     * @return list<array<string, mixed>>
     */
    public function flatten(): array
    {
        return [$this->toArray()];
    }

    /**
     * @internal Used by {@see MenuBranch::children()}.
     */
    public function assignParentIfMissing(string $parentRef): void
    {
        if ($this->parent === null) {
            $this->parent = $parentRef;
        }
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

        if ($this->href !== null) {
            $row['href'] = $this->href;
        } elseif ($this->view !== null) {
            $row['href'] = '/velm/views/'.($this->viewModule ?? $this->module).'/'.$this->view;
        }

        return $row;
    }
}
