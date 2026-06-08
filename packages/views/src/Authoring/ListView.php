<?php

declare(strict_types=1);

namespace Velm\Views\Authoring;

use Velm\Views\Authoring\Contracts\ViewDeclaration;

final class ListView implements ViewDeclaration
{
    /** @var list<mixed> */
    private array $fields = [];

    private ?string $model = null;

    private ?string $title = null;

    private ?string $formView = null;

    /** Detail view opened from the list (read-only). */
    private ?string $detailView = null;

    /** @deprecated Use {@see detailView()} */
    private ?string $recordView = null;

    /** Form view used for explicit edit navigation. Defaults to {@see $formView}. */
    private ?string $editView = null;

    private ?bool $clickToOpen = null;

    /** @var list<array{action: string, label: string, icon: string, href?: string}> */
    private array $rowActions = [];

    /** @var list<array<string, mixed>> */
    private array $pageActions = [];

    private bool $readonly = false;

    /** @var 'simple'|'full'|null */
    private ?string $pagination = null;

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

    /**
     * @param  list<mixed>  $fields
     */
    public function columns(array $fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function formView(string $formView): self
    {
        $this->formView = $formView;

        return $this;
    }

    /**
     * Target detail view when a list row is opened (read-only display).
     */
    public function detailView(string $detailView): self
    {
        $this->detailView = $detailView;

        return $this;
    }

    /**
     * @deprecated Use {@see detailView()}
     */
    public function recordView(string $recordView): self
    {
        return $this->detailView($recordView);
    }

    /**
     * Target form for explicit edit navigation (e.g. from the record page).
     */
    public function editView(string $editView): self
    {
        $this->editView = $editView;

        return $this;
    }

    /**
     * Navigate to the detail view when the row is clicked (requires {@see detailView()}).
     */
    public function clickToOpen(bool $enabled = true): self
    {
        $this->clickToOpen = $enabled;

        return $this;
    }

    /**
     * Read-only list: no create button, no default delete action, no edit navigation.
     */
    public function readonly(bool $readonly = true): self
    {
        $this->readonly = $readonly;

        return $this;
    }

    /**
     * Pagination style for the list: {@see simple()} (prev/next) or {@see full()} (page numbers).
     */
    public function pagination(string $style): self
    {
        if (! in_array($style, ['simple', 'full'], true)) {
            throw new \InvalidArgumentException("List pagination must be 'simple' or 'full', got {$style}.");
        }

        $this->pagination = $style;

        return $this;
    }

    public function simplePagination(): self
    {
        return $this->pagination('simple');
    }

    public function fullPagination(): self
    {
        return $this->pagination('full');
    }

    /**
     * @param  list<ListRowAction|array{action: string, label: string, href?: string}>  $actions
     */
    public function rowActions(array $actions): self
    {
        $this->rowActions = array_map(
            static fn (ListRowAction|array $action): array => $action instanceof ListRowAction
                ? $action->toArray()
                : $action,
            $actions,
        );

        return $this;
    }

    /**
     * @param  list<Action|array<string, mixed>>  $actions
     */
    public function pageActions(array $actions): self
    {
        $this->pageActions = array_map(
            static fn (Action|array $action): array => $action instanceof Action
                ? $action->toArray()
                : $action,
            $actions,
        );

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if ($this->model === null) {
            throw new \LogicException("List view {$this->name} is missing model().");
        }

        $arch = [
            'fields' => array_map(
                static fn (mixed $field): array => $field instanceof ViewDeclaration ? $field->toArray() : (is_array($field) ? $field : ['name' => (string) $field]),
                $this->fields,
            ),
        ];

        if ($this->title !== null) {
            $arch['title'] = $this->title;
        }

        if ($this->formView !== null) {
            $arch['form_view'] = $this->formView;
        }

        if ($this->detailView !== null) {
            $arch['detail_view'] = $this->detailView;
        }

        if ($this->editView !== null) {
            $arch['edit_view'] = $this->editView;
        }

        if ($this->clickToOpen !== null) {
            $arch['click_to_open'] = $this->clickToOpen;
        } elseif ($this->detailView !== null) {
            $arch['click_to_open'] = true;
        }

        if ($this->rowActions !== []) {
            $arch['row_actions'] = $this->rowActions;
        }

        if ($this->pageActions !== []) {
            $arch['page_actions'] = $this->pageActions;
        }

        if ($this->readonly) {
            $arch['readonly'] = true;
        }

        if ($this->pagination !== null) {
            $arch['pagination'] = $this->pagination;
        }

        return [
            'name' => $this->name,
            'model' => $this->model,
            'view_type' => 'list',
            'arch' => $arch,
        ];
    }
}
