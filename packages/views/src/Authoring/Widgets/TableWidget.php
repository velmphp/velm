<?php

declare(strict_types=1);

namespace Velm\Views\Authoring\Widgets;

final class TableWidget
{
    private ?string $title = null;

    private ?string $view = null;

    private int $limit = 5;

    private string $size = 'half';

    private ?string $icon = null;

    private function __construct(
        private readonly string $id,
    ) {}

    public static function make(string $id): self
    {
        return new self($id);
    }

    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function view(string $view): self
    {
        $this->view = $view;

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = max(1, $limit);

        return $this;
    }

    public function size(string $size): self
    {
        $this->size = $size;

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
        if ($this->view === null) {
            throw new \LogicException("Table widget {$this->id} is missing view().");
        }

        $widget = [
            'type' => 'table',
            'id' => $this->id,
            'view' => $this->view,
            'limit' => $this->limit,
            'size' => $this->size,
        ];

        if ($this->title !== null) {
            $widget['title'] = $this->title;
        }

        if ($this->icon !== null) {
            $widget['icon'] = $this->icon;
        }

        return $widget;
    }
}
