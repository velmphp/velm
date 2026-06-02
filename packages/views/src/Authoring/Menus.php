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

    public function group(string $name, string $label): MenuGroup
    {
        return MenuGroup::make($name, $label);
    }

    public function item(string $name, string $label): MenuItem
    {
        return MenuItem::make($this->module, $name, $label);
    }

    /**
     * Parent reference within this module (`partners.business` → `partners.partners.business`).
     */
    public function parentRef(string $name): string
    {
        return "{$this->module}.{$name}";
    }
}
