<?php

declare(strict_types=1);

namespace Velm\Views\Authoring;

use Velm\Views\Authoring\Contracts\ViewDeclaration;

final class PivotView implements ViewDeclaration
{
    private ?string $model = null;

    private ?string $title = null;

    /** @var list<string> */
    private array $rows = [];

    /** @var list<string> */
    private array $cols = [];

    /** @var list<string> */
    private array $measures = ['__count'];

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

    /**
     * @param  list<string>  $rows
     */
    public function rows(array $rows): self
    {
        $this->rows = array_values($rows);

        return $this;
    }

    /**
     * @param  list<string>  $cols
     */
    public function cols(array $cols): self
    {
        $this->cols = array_values($cols);

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
            throw new \LogicException("Pivot view {$this->name} is missing model().");
        }

        if ($this->rows === []) {
            throw new \LogicException("Pivot view {$this->name} is missing rows().");
        }

        $arch = [
            'rows' => $this->rows,
            'cols' => $this->cols,
            'measures' => $this->measures,
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
            'view_type' => 'pivot',
            'arch' => $arch,
        ];
    }
}
