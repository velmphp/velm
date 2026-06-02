<?php

declare(strict_types=1);

namespace Velm\Views\Authoring;

use Velm\Views\Authoring\Contracts\MenuDeclaration;

/**
 * Menu group with optional nested children (PyVelm {@see MenuBranch} parity).
 *
 * @example
 * $m->group('business', 'Business', icon: 'home')->children([
 *     $m->group('business.directory', 'Directory')->children([
 *         $m->item('business.partners', 'Partners', view: 'partner.list'),
 *     ]),
 * ]);
 */
final class MenuBranch implements MenuDeclaration
{
    private ?string $parent = null;

    private int $sequence = 10;

    private ?string $icon = null;

    /** @var list<MenuDeclaration> */
    private array $children = [];

    private function __construct(
        private readonly string $module,
        private readonly string $name,
        private readonly string $label,
    ) {}

    public static function make(string $module, string $name, string $label): self
    {
        return new self($module, $name, $label);
    }

    public function parentRef(string $moduleDotName): self
    {
        $this->parent = $moduleDotName;

        return $this;
    }

    /**
     * Cross-module parent, e.g. parent('admin', 'settings').
     */
    public function parent(string $parentModule, string $parentName): self
    {
        $this->parent = "{$parentModule}.{$parentName}";

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

    public function children(MenuDeclaration ...$entries): self
    {
        $parentRef = "{$this->module}.{$this->name}";

        foreach ($entries as $entry) {
            $this->inheritParentOnChild($entry, $parentRef);
            $this->children[] = $entry;
        }

        return $this;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function flatten(): array
    {
        $rows = [$this->groupRow()];

        foreach ($this->children as $child) {
            array_push($rows, ...$child->flatten());
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function groupRow(): array
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

    private function inheritParentOnChild(MenuDeclaration $entry, string $parentRef): void
    {
        if ($entry instanceof MenuItem) {
            $entry->assignParentIfMissing($parentRef);

            return;
        }

        if ($entry instanceof self) {
            $entry->assignParentIfMissing($parentRef);
        }
    }

    /**
     * @internal Called by {@see children()} on ancestors.
     */
    public function assignParentIfMissing(string $parentRef): void
    {
        if ($this->parent === null) {
            $this->parent = $parentRef;
        }
    }
}
