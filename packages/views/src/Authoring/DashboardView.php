<?php

declare(strict_types=1);

namespace Velm\Views\Authoring;

use Velm\Views\Authoring\Contracts\ViewDeclaration;
use Velm\Views\Authoring\Widgets\ChartWidget;
use Velm\Views\Authoring\Widgets\StatWidget;
use Velm\Views\Authoring\Widgets\TableWidget;

final class DashboardView implements ViewDeclaration
{
    private ?string $model = null;

    private ?string $title = null;

    private int $columns = 2;

    /** @var list<array<string, mixed>> */
    private array $widgets = [];

    /** @var list<mixed> */
    private array $domain = [];

    private ?string $listView = null;

    private function __construct(
        private readonly string $name,
    ) {}

    public static function make(string $name): self
    {
        return new self($name);
    }

    public function model(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function columns(int $columns): self
    {
        $this->columns = max(1, $columns);

        return $this;
    }

    /**
     * @param  list<StatWidget|TableWidget|ChartWidget>  $widgets
     */
    public function widgets(array $widgets): self
    {
        $this->widgets = array_map(
            static fn (StatWidget|TableWidget|ChartWidget $widget): array => $widget->toArray(),
            $widgets,
        );

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

    public function listView(string $listView): self
    {
        $this->listView = $listView;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if ($this->model === null) {
            throw new \LogicException("Dashboard view {$this->name} is missing model().");
        }

        if ($this->widgets === []) {
            throw new \LogicException("Dashboard view {$this->name} is missing widgets().");
        }

        $arch = [
            'columns' => $this->columns,
            'widgets' => $this->widgets,
        ];

        if ($this->title !== null) {
            $arch['title'] = $this->title;
        }

        if ($this->domain !== []) {
            $arch['domain'] = $this->domain;
        }

        if ($this->listView !== null) {
            $arch['list_view'] = $this->listView;
        }

        return [
            'name' => $this->name,
            'model' => $this->model,
            'view_type' => 'dashboard',
            'arch' => $arch,
        ];
    }
}
