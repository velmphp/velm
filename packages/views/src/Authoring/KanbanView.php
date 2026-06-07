<?php

declare(strict_types=1);

namespace Velm\Views\Authoring;

use Velm\Views\Authoring\Contracts\ViewDeclaration;

final class KanbanView implements ViewDeclaration
{
    private ?string $model = null;

    private ?string $title = null;

    private ?string $groupBy = null;

    private ?Card $card = null;

    private ?string $formView = null;

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

    public function card(Card $card): self
    {
        $this->card = $card;

        return $this;
    }

    public function formView(string $formView): self
    {
        $this->formView = $formView;

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
            throw new \LogicException("Kanban view {$this->name} is missing model().");
        }

        if ($this->card === null) {
            throw new \LogicException("Kanban view {$this->name} is missing card().");
        }

        $arch = [
            'card' => $this->card->toArray(),
        ];

        if ($this->groupBy !== null) {
            $arch['group_by'] = $this->groupBy;
        }

        if ($this->title !== null) {
            $arch['title'] = $this->title;
        }

        if ($this->formView !== null) {
            $arch['form_view'] = $this->formView;
        }

        if ($this->listView !== null) {
            $arch['list_view'] = $this->listView;
        }

        return [
            'name' => $this->name,
            'model' => $this->model,
            'view_type' => 'kanban',
            'arch' => $arch,
        ];
    }
}
