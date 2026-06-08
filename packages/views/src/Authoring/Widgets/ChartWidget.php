<?php

declare(strict_types=1);

namespace Velm\Views\Authoring\Widgets;

final class ChartWidget
{
    private ?string $title = null;

    private ?string $view = null;

    private int|string $colspan = 1;

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

    /**
     * Column span inside the dashboard grid. Use `full` to span the entire row.
     */
    public function colspan(int|string $colspan): self
    {
        if ($colspan === 'full') {
            $this->colspan = 'full';

            return $this;
        }

        if ((int) $colspan < 1) {
            throw new \InvalidArgumentException('colspan must be at least 1.');
        }

        $this->colspan = (int) $colspan;

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
            throw new \LogicException("Chart widget {$this->id} is missing view().");
        }

        $widget = [
            'type' => 'chart',
            'id' => $this->id,
            'view' => $this->view,
            'colspan' => $this->colspan,
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
