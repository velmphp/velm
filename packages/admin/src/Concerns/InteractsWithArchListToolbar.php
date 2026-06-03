<?php

declare(strict_types=1);

namespace Velm\Admin\Concerns;

use Velm\Environment;
use Velm\Admin\Arch\ListColumnHeaders;
use Velm\Admin\Arch\ListQuery;
use Velm\Web\Api\Many2oneSearch;

trait InteractsWithArchListToolbar
{
    public string $listSearch = '';

    /** @var list<array{field: ?string, op: string, value: mixed, label: string}> */
    public array $listFilterChips = [];

    public ?string $listGroupBy = null;

    public bool $listFiltersPanelOpen = false;

    public bool $listColumnsPanelOpen = false;

    public ?string $listOpenFilterField = null;

    /** @var array<string, string> */
    public array $listM2oQuery = [];

    /** @var array<string, list<array{id: int, label: string}>> */
    public array $listM2oResults = [];

    public function listQuery(): ListQuery
    {
        return new ListQuery(
            search: $this->listSearch,
            filterChips: $this->listFilterChips,
            groupBy: $this->listGroupBy,
        );
    }

    /**
     * @return list<array{name: string, label: string, filter_kind: string, group_kind: string, comodel: ?string, visible_default: bool}>
     */
    public function listHeaders(): array
    {
        return app(ListColumnHeaders::class)->fromArch($this->arch(), app(Environment::class));
    }

    public function updatedListSearch(): void
    {
        $this->syncListSearchChip();
        $this->refreshList();
    }

    public function addBooleanListFilter(string $field, bool $value): void
    {
        $header = $this->findListHeader($field);
        if ($header === null) {
            return;
        }

        $this->removeListFilterChipsForField($field);
        $this->listFilterChips[] = [
            'field' => $field,
            'op' => '=',
            'value' => $value,
            'label' => $header['label'].': '.($value ? 'Yes' : 'No'),
        ];
        $this->listOpenFilterField = null;
        $this->refreshList();
    }

    public function addM2oListFilter(string $field, int $id, string $label): void
    {
        $header = $this->findListHeader($field);
        if ($header === null) {
            return;
        }

        $this->removeListFilterChipsForField($field);
        $this->listFilterChips[] = [
            'field' => $field,
            'op' => '=',
            'value' => $id,
            'label' => $header['label'].': '.$label,
        ];
        $this->listOpenFilterField = null;
        $this->listM2oQuery[$field] = '';
        $this->listM2oResults[$field] = [];
        $this->refreshList();
    }

    public function updatedListM2oQuery(mixed $value, string $name): void
    {
        $this->searchListM2o($name);
    }

    public function searchListM2o(string $field): void
    {
        $header = $this->findListHeader($field);
        if ($header === null || $header['comodel'] === null) {
            return;
        }

        $query = trim($this->listM2oQuery[$field] ?? '');
        $comodel = $header['comodel'];
        $payload = app(Many2oneSearch::class)->search(
            app(Environment::class),
            $comodel,
            $query,
            20,
        );

        $this->listM2oResults[$field] = $payload['results'];
    }

    public function removeListFilterChipByField(string $field): void
    {
        $this->removeListFilterChipsForField($field);
        $this->refreshList();
    }

    public function toggleListGroupBy(string $field): void
    {
        $this->setListGroupBy($this->listGroupBy === $field ? null : $field);
    }

    public function clearListQuery(): void
    {
        $this->listSearch = '';
        $this->listFilterChips = [];
        $this->listGroupBy = null;
        $this->refreshList();
    }

    public function setListGroupBy(?string $field): void
    {
        if ($field !== null) {
            $header = $this->findListHeader($field);
            if ($header === null || $header['group_kind'] === 'none') {
                return;
            }
        }

        $this->listGroupBy = $field;
        $this->listFiltersPanelOpen = false;
        $this->refreshList();
    }

    public function toggleListFilterField(string $field): void
    {
        $this->listOpenFilterField = $this->listOpenFilterField === $field ? null : $field;

        if ($this->listOpenFilterField === $field) {
            $this->searchListM2o($field);
        }
    }

    public function toggleListColumn(string $name): void
    {
        $visible = collect($this->listColumnVisibility)->filter(fn (bool $v): bool => $v)->count();

        if (($this->listColumnVisibility[$name] ?? true) && $visible <= 1) {
            return;
        }

        $this->listColumnVisibility[$name] = ! ($this->listColumnVisibility[$name] ?? true);
    }

    public function isListColumnVisible(string $name): bool
    {
        return $this->listColumnVisibility[$name] ?? true;
    }

    public function listGroupByLabel(): string
    {
        if ($this->listGroupBy === null) {
            return '';
        }

        $header = $this->findListHeader($this->listGroupBy);

        return $header['label'] ?? $this->listGroupBy;
    }

    /**
     * @return list<array{name: string, label: string, filter_kind: string, group_kind: string, comodel: ?string, visible_default: bool}>
     */
    public function filterableListHeaders(): array
    {
        return array_values(array_filter(
            $this->listHeaders(),
            static fn (array $header): bool => $header['filter_kind'] !== 'none' && $header['filter_kind'] !== 'text',
        ));
    }

    /**
     * @return list<array{name: string, label: string, filter_kind: string, group_kind: string, comodel: ?string, visible_default: bool}>
     */
    public function groupableListHeaders(): array
    {
        return array_values(array_filter(
            $this->listHeaders(),
            static fn (array $header): bool => $header['group_kind'] !== 'none',
        ));
    }

    /**
     * @return array{name: string, label: string, filter_kind: string, group_kind: string, comodel: ?string, visible_default: bool}|null
     */
    protected function findListHeader(string $name): ?array
    {
        foreach ($this->listHeaders() as $header) {
            if ($header['name'] === $name) {
                return $header;
            }
        }

        return null;
    }

    /**
     * @return list<array{field: ?string, op: string, value: mixed, label: string}>
     */
    public function listDisplayChips(): array
    {
        $chips = $this->listFilterChips;
        $search = trim($this->listSearch);

        if ($search !== '') {
            array_unshift($chips, [
                'field' => null,
                'op' => 'ilike',
                'value' => $search,
                'label' => 'Search: "'.$search.'"',
            ]);
        }

        return $chips;
    }

    protected function refreshList(): void
    {
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    private function removeListFilterChipsForField(string $field): void
    {
        $this->listFilterChips = array_values(array_filter(
            $this->listFilterChips,
            static fn (array $chip): bool => ($chip['field'] ?? null) !== $field,
        ));
    }

    private function syncListSearchChip(): void
    {
        $this->listFilterChips = array_values(array_filter(
            $this->listFilterChips,
            static fn (array $chip): bool => ($chip['field'] ?? null) !== null,
        ));
    }
}
