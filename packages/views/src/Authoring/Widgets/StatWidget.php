<?php

declare(strict_types=1);

namespace Velm\Views\Authoring\Widgets;

final class StatWidget
{
    private ?string $title = null;

    private ?string $model = null;

    /** @var list<mixed> */
    private array $domain = [];

    private ?string $measure = null;

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

    public function model(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    /**
     * @param  list<mixed>  $domain
     */
    public function domain(array $domain): self
    {
        $this->domain = $domain;

        return $this;
    }

    public function measure(string $measure): self
    {
        $this->measure = $measure;

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
        $widget = [
            'type' => 'stat',
            'id' => $this->id,
            'colspan' => $this->colspan,
        ];

        if ($this->title !== null) {
            $widget['title'] = $this->title;
        }

        if ($this->model !== null) {
            $widget['model'] = $this->model;
        }

        if ($this->domain !== []) {
            $widget['domain'] = $this->domain;
        }

        if ($this->measure !== null) {
            $widget['measure'] = $this->measure;
        }

        if ($this->icon !== null) {
            $widget['icon'] = $this->icon;
        }

        return $widget;
    }
}
