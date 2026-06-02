<?php

declare(strict_types=1);

namespace Velm\Views\Authoring;

/**
 * Module-scoped menu authoring helper (PyVelm {@see Menus} parity).
 */
final class Menus
{
    public function __construct(
        private readonly string $module,
    ) {}

    public function group(string $name, string $label): MenuBranch
    {
        return MenuBranch::make($this->module, $name, $label);
    }

    public function item(string $name, string $label): MenuItem
    {
        return MenuItem::make($this->module, $name, $label);
    }

    /**
     * Parent reference within this module (`business` → `partners.business`).
     */
    public function parentRef(string $name): string
    {
        return "{$this->module}.{$name}";
    }

    /**
     * Cross-module parent reference for explicit {@see MenuItem::parentRef()}.
     */
    public function parent(string $parentModule, string $parentName): string
    {
        return "{$parentModule}.{$parentName}";
    }
}
