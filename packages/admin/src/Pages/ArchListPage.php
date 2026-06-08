<?php

declare(strict_types=1);

namespace Velm\Admin\Pages;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\WithPagination;
use Velm\Environment;
use Velm\Admin\Arch\ArchSchemaBuilder;
use Velm\Admin\Arch\ArchTableConfigurator;
use Velm\Admin\Arch\ListColumn;
use Velm\Admin\Concerns\InteractsWithArchListToolbar;
use Velm\Admin\Concerns\InteractsWithVelmListBulkActions;
use Velm\Admin\Concerns\InteractsWithVelmListPresentation;
use Velm\Admin\Concerns\InteractsWithVelmViewActions;
use Velm\Admin\Support\ListPageSize;
use Velm\Admin\Support\ListPagination;

abstract class ArchListPage extends VelmShellPage
{
    use InteractsWithArchListToolbar;
    use InteractsWithVelmListBulkActions;
    use InteractsWithVelmListPresentation;
    use InteractsWithVelmViewActions;
    use WithPagination;

    public int $listPerPage = -1;

    /** @var array<string, bool> */
    public array $listColumnVisibility = [];

    /**
     * @return array<string, mixed>
     */
    abstract protected function arch(): array;

    public function mount(): void
    {
        $this->bootListColumnVisibility();
        if (! in_array($this->listPerPage, ListPageSize::values(), true)) {
            $this->listPerPage = ListPageSize::default();
        }
    }

    public function updatedListPerPage(): void
    {
        $this->listPerPage = ListPageSize::normalize($this->listPerPage);
        $this->clearListSelection();
        $this->resetPage();
    }

    public function updatedListGroupBy(): void
    {
        $this->clearListSelection();
    }

    /**
     * @return list<array{value: int, label: string}>
     */
    public function listPageSizeOptions(): array
    {
        return ListPageSize::options();
    }

    public function getTitle(): string|Htmlable
    {
        $title = $this->arch()['title'] ?? null;

        if (is_string($title) && $title !== '') {
            return $title;
        }

        return parent::getTitle();
    }

    /**
     * @return list<ListColumn>
     */
    public function listColumns(): array
    {
        return app(ArchSchemaBuilder::class)->buildListColumns($this->arch(), app(Environment::class));
    }

    public function listRecords(): Collection
    {
        return app(ArchTableConfigurator::class)->fetchRecords(
            $this->arch(),
            app(Environment::class),
            $this->listQuery(),
        );
    }

    public function paginatedListRecords(): LengthAwarePaginator
    {
        $items = $this->listRecords();
        $total = $items->count();
        $perPage = ListPageSize::effectivePerPage($this->listPerPage, $total);
        $page = ListPageSize::isAll($this->listPerPage) ? 1 : max(1, $this->getPage());
        $pageItems = ListPageSize::isAll($this->listPerPage)
            ? $items->values()->all()
            : $items->forPage($page, $perPage)->values()->all();

        return new LengthAwarePaginator(
            $pageItems,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'pageName' => 'page'],
        );
    }

    public function listPaginationStyle(): string
    {
        $archStyle = $this->arch()['pagination'] ?? null;

        return ListPagination::resolveStyle(is_string($archStyle) ? $archStyle : null);
    }

    public function listPaginationView(): string
    {
        return ListPagination::viewForStyle($this->listPaginationStyle());
    }

    /**
     * @return list<array{label: string, rows: list<array<string, mixed>>}>
     */
    public function groupedListRecords(): array
    {
        $records = $this->listRecords();
        $field = $this->listGroupBy;
        $header = $field !== null ? $this->findListHeader($field) : null;
        $env = app(Environment::class);
        $groups = [];

        foreach ($records as $record) {
            $value = $field !== null ? ($record[$field] ?? null) : null;
            $label = $this->groupLabel($header, $value, $env);
            $groups[$label] ??= ['label' => $label, 'rows' => []];
            $groups[$label]['rows'][] = $record;
        }

        return array_values($groups);
    }

    public function formatListCell(ListColumn $column, mixed $value): string
    {
        return app(ArchSchemaBuilder::class)->formatListCell($column, $value, app(Environment::class));
    }

    public function updateListToggle(int $recordId, string $field, mixed $value): void
    {
        $model = (string) $this->arch()['model'];
        app(Environment::class)->browse($model, [$recordId])->write([
            $field => filter_var($value, FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    public function deleteListRecord(int $recordId): void
    {
        if ($recordId <= 0) {
            return;
        }

        $model = (string) $this->arch()['model'];

        if ($model === '') {
            return;
        }

        app(Environment::class)->browse($model, [$recordId])->unlink();

        $this->resetPage();
    }

    public function showListColumnsPanel(): bool
    {
        return true;
    }

    public function showListGroupByPanel(): bool
    {
        return true;
    }

    public function render()
    {
        return view('velm-ui::list.page');
    }

    protected function bootListColumnVisibility(): void
    {
        foreach ($this->listHeaders() as $header) {
            $this->listColumnVisibility[$header['name']] = $header['visible_default'] ?? true;
        }
    }

    /**
     * @param  array{name: string, label: string, filter_kind: string, group_kind: string, comodel: ?string, visible_default: bool}|null  $header
     */
    private function groupLabel(?array $header, mixed $value, Environment $env): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if ($header === null) {
            return (string) $value;
        }

        if ($header['group_kind'] === 'boolean') {
            return $value ? 'Yes' : 'No';
        }

        if ($header['group_kind'] === 'm2o' && $header['comodel'] !== null) {
            $rows = $env->browse($header['comodel'], [(int) $value])->read();

            return (string) ($rows[0]['display_name'] ?? $value);
        }

        return (string) $value;
    }
}
