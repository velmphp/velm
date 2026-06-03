<?php

declare(strict_types=1);

namespace Velm\Views\Authoring;

/**
 * Declares a single list row action (rendered in the actions column).
 *
 * @phpstan-type ListRowActionArray array{action: string, label: string, icon: string, href?: string}
 */
final class ListRowAction
{
    private function __construct(
        private readonly string $action,
        private readonly string $label,
        private readonly string $icon,
        private readonly ?string $href = null,
    ) {}

    public static function open(string $label = 'Open', ?string $icon = null): self
    {
        return new self('open', $label, $icon ?? 'heroicon-o-eye');
    }

    public static function edit(string $label = 'Edit', ?string $icon = null): self
    {
        return new self('edit', $label, $icon ?? 'heroicon-o-pencil-square');
    }

    public static function delete(string $label = 'Delete', ?string $icon = null): self
    {
        return new self('delete', $label, $icon ?? 'heroicon-o-trash');
    }

    public static function link(string $label, string $href, ?string $icon = null): self
    {
        return new self('link', $label, $icon ?? 'heroicon-o-arrow-top-right-on-square', $href);
    }

    public function icon(string $icon): self
    {
        return new self($this->action, $this->label, $icon, $this->href);
    }

    /**
     * @return ListRowActionArray
     */
    public function toArray(): array
    {
        $row = [
            'action' => $this->action,
            'label' => $this->label,
            'icon' => $this->icon,
        ];

        if ($this->href !== null && $this->href !== '') {
            $row['href'] = $this->href;
        }

        return $row;
    }
}
