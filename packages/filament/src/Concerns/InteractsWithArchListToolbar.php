<?php

declare(strict_types=1);

namespace Velm\Filament\Concerns;

use Velm\Environment;
use Velm\Filament\Arch\ListColumnHeaders;
use Velm\Filament\Arch\ListQuery;
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
        return app(ListColumnHeaders::class)->fromArch(static::arch(), app(Environment::class));
    }

    public function updatedListSearch(): void
    {
        $this->syncListSearchChip();
        $this->resetTable();
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
        $this->resetTable();
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
        $this->resetTable();
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
        $this->resetTable();
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
        $this->resetTable();
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
        $this->resetTable();
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
        $state = $this->tableColumns;
        $changed = false;

        foreach ($state as &$item) {
            if ($item['type'] !== 'column' || $item['name'] !== $name) {
                continue;
            }

            $visible = collect($state)
                ->filter(fn (array $column): bool => $column['type'] === 'column' && $column['isToggled'])
                ->count();

            if ($item['isToggled'] && $visible <= 1) {
                return;
            }

            $item['isToggled'] = ! $item['isToggled'];
            $changed = true;
            break;
        }
        unset($item);

        if ($changed) {
            $this->applyTableColumnManager($state);
        }
    }

    public function isListColumnVisible(string $name): bool
    {
        return ! $this->isTableColumnToggledHidden($name);
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
}
