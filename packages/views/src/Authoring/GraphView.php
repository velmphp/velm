<?php

declare(strict_types=1);

namespace Velm\Views\Authoring;

use Velm\Views\Authoring\Contracts\ViewDeclaration;

final class GraphView implements ViewDeclaration
{
    private ?string $model = null;

    private ?string $title = null;

    private ?string $groupBy = null;

    /** @var list<string> */
    private array $measures = [];

    private string $chart = 'bar';

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

    public function groupBy(string $field): self
    {
        $this->groupBy = $field;

        return $this;
    }

    public function measure(string $spec): self
    {
        $this->measures = [$spec];

        return $this;
    }

    /**
     * @param  list<string>  $measures
     */
    public function measures(array $measures): self
    {
        $this->measures = array_values($measures);

        return $this;
    }

    public function chart(string $chart): self
    {
        $this->chart = $chart;

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
            throw new \LogicException("Graph view {$this->name} is missing model().");
        }

        if ($this->groupBy === null) {
            throw new \LogicException("Graph view {$this->name} is missing groupBy().");
        }

        if ($this->measures === []) {
            throw new \LogicException("Graph view {$this->name} is missing measure().");
        }

        $arch = [
            'group_by' => $this->groupBy,
            'measures' => $this->measures,
            'chart' => $this->chart,
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
            'view_type' => 'graph',
            'arch' => $arch,
        ];
    }
}
