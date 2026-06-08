<?php

declare(strict_types=1);

namespace Velm\Admin\Concerns;

use Velm\Admin\Support\VelmNotify;
use Velm\Environment;
use Velm\Views\Authoring\Action;
use Velm\Views\Authoring\ActionVariant;

trait InteractsWithVelmListBulkActions
{
    /** @var list<int> */
    public array $listSelectedIds = [];

    public function listShowsSelection(): bool
    {
        return $this->listBulkActions() !== [];
    }

    public function listSelectedCount(): int
    {
        return count($this->normalizedListSelectedIds());
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listBulkActions(): array
    {
        $raw = $this->arch()['bulk_actions'] ?? [];

        if (! is_array($raw)) {
            $raw = [];
        }

        $actions = $this->resolveViewActions($this->appendDefaultListBulkActionsRaw($raw), 0, 'bulk');

        return array_values(array_filter(
            $actions,
            static fn (array $action): bool => ($action['wire'] ?? '') !== '' || ($action['url'] ?? '') !== '',
        ));
    }

    public function listAllPageSelected(): bool
    {
        $pageIds = $this->listCurrentPageRecordIds();

        if ($pageIds === []) {
            return false;
        }

        $selected = array_flip($this->normalizedListSelectedIds());

        foreach ($pageIds as $id) {
            if (! isset($selected[$id])) {
                return false;
            }
        }

        return true;
    }

    public function toggleListSelectAllOnPage(): void
    {
        $pageIds = $this->listCurrentPageRecordIds();

        if ($pageIds === []) {
            return;
        }

        if ($this->listAllPageSelected()) {
            $remove = array_flip($pageIds);
            $this->listSelectedIds = array_values(array_filter(
                $this->normalizedListSelectedIds(),
                static fn (int $id): bool => ! isset($remove[$id]),
            ));

            return;
        }

        $this->listSelectedIds = array_values(array_unique([
            ...$this->normalizedListSelectedIds(),
            ...$pageIds,
        ]));
    }

    public function clearListSelection(): void
    {
        $this->listSelectedIds = [];
    }

    public function updatedListSelectedIds(): void
    {
        $this->listSelectedIds = $this->normalizedListSelectedIds();
    }

    public function updatedPage(): void
    {
        $this->clearListSelection();
    }

    public function runListBulkWireAction(string $actionKey): void
    {
        $action = $this->findListBulkAction($actionKey);

        if ($action === null) {
            return;
        }

        $wire = (string) ($action['wire'] ?? '');

        if ($wire === '') {
            return;
        }

        $ids = $this->resolveListBulkTargetIds();

        if ($ids === []) {
            return;
        }

        match ($wire) {
            'delete' => $this->bulkDeleteListRecords($ids),
            default => null,
        };

        $this->listSelectedIds = array_values(array_diff(
            $this->normalizedListSelectedIds(),
            $ids,
        ));
    }

    /**
     * @param  list<int>  $ids
     */
    protected function bulkDeleteListRecords(array $ids): void
    {
        $model = (string) $this->arch()['model'];

        if ($model === '' || $ids === []) {
            return;
        }

        app(Environment::class)->browse($model, $ids)->unlink();

        $this->resetPage();

        VelmNotify::flash('success', __('Deleted :count records.', ['count' => count($ids)]));
    }

    /**
     * @return list<int>
     */
    protected function listCurrentPageRecordIds(): array
    {
        if (filled($this->listGroupBy ?? null)) {
            $ids = [];

            foreach ($this->groupedListRecords() as $group) {
                foreach ($group['rows'] as $row) {
                    $id = (int) ($row['id'] ?? 0);

                    if ($id > 0) {
                        $ids[] = $id;
                    }
                }
            }

            return $ids;
        }

        return array_values(array_filter(array_map(
            static fn (array $row): int => (int) ($row['id'] ?? 0),
            $this->paginatedListRecords()->items(),
        ), static fn (int $id): bool => $id > 0));
    }

    /**
     * @return list<int>
     */
    protected function resolveListBulkTargetIds(): array
    {
        $ids = $this->normalizedListSelectedIds();

        if ($ids === []) {
            return [];
        }

        $model = (string) $this->arch()['model'];

        if ($model === '') {
            return [];
        }

        return app(Environment::class)->browse($model, $ids)->ids();
    }

    /**
     * @return list<int>
     */
    private function normalizedListSelectedIds(): array
    {
        $ids = [];

        foreach ($this->listSelectedIds as $raw) {
            $id = (int) $raw;

            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  list<mixed>  $raw
     * @return list<array<string, mixed>>
     */
    private function appendDefaultListBulkActionsRaw(array $raw): array
    {
        $hasDelete = false;

        foreach ($raw as $action) {
            if (! is_array($action)) {
                continue;
            }

            if ((string) ($action['wire'] ?? '') === 'delete') {
                $hasDelete = true;
                break;
            }
        }

        if (
            ! $hasDelete
            && ! $this->listIsReadonly()
            && $this->listModelCanUnlink()
        ) {
            $raw[] = Action::make('Delete')
                ->wire('delete')
                ->perm('unlink')
                ->confirm('Delete the selected records? This cannot be undone.')
                ->variant(ActionVariant::Danger)
                ->toArray();
        }

        return $raw;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findListBulkAction(string $actionKey): ?array
    {
        foreach ($this->listBulkActions() as $action) {
            if ((string) ($action['action_key'] ?? '') === $actionKey) {
                return $action;
            }
        }

        return null;
    }
}
