<?php

declare(strict_types=1);

namespace Velm\Views\Sync;

final class UiSyncDiff
{
    /** @var list<string> */
    public array $newViews = [];

    /** @var list<string> */
    public array $changedViews = [];

    /** @var list<string> */
    public array $removedViews = [];

    /** @var list<string> */
    public array $newMenus = [];

    /** @var list<string> */
    public array $changedMenus = [];

    /** @var list<string> */
    public array $removedMenus = [];

    public function hasChanges(): bool
    {
        return $this->newViews !== []
            || $this->changedViews !== []
            || $this->removedViews !== []
            || $this->newMenus !== []
            || $this->changedMenus !== []
            || $this->removedMenus !== [];
    }

    public function summary(): string
    {
        $parts = [];

        if ($this->newViews !== []) {
            $parts[] = count($this->newViews).' new view(s)';
        }

        if ($this->changedViews !== []) {
            $parts[] = count($this->changedViews).' changed view(s)';
        }

        if ($this->removedViews !== []) {
            $parts[] = count($this->removedViews).' removed view(s)';
        }

        if ($this->newMenus !== []) {
            $parts[] = count($this->newMenus).' new menu(s)';
        }

        if ($this->changedMenus !== []) {
            $parts[] = count($this->changedMenus).' changed menu(s)';
        }

        if ($this->removedMenus !== []) {
            $parts[] = count($this->removedMenus).' removed menu(s)';
        }

        return $parts === [] ? 'Views or menus changed on disk' : implode(', ', $parts);
    }
}
