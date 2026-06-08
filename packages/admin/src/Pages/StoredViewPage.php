<?php

declare(strict_types=1);

namespace Velm\Admin\Pages;

use Velm\Admin\Arch\GraphDataBuilder;
use Velm\Admin\Arch\KanbanBoardBuilder;
use Velm\Admin\Arch\ListColumnHeaders;
use Velm\Admin\Arch\PivotDataBuilder;
use Velm\Admin\Arch\PivotGridBuilder;
use Velm\Admin\Arch\ViewFieldCatalog;
use Velm\Admin\Concerns\ReconcilesVelmModuleUi;
use Illuminate\Pagination\LengthAwarePaginator;
use Velm\Admin\Support\ListPageSize;
use Velm\Admin\Support\ListPagination;
use Velm\Admin\Support\ResolvesStoredView;
use Velm\Admin\Support\StoredViewRoutes;
use Velm\Environment;
use Velm\Views\Arch\ArchNormalizer;
use Velm\Views\ViewRegistry;

class StoredViewPage extends ArchListPage
{
    use ReconcilesVelmModuleUi;
    use ResolvesStoredView;

    protected static ?string $slug = 'views/{module}/{viewName}';

    public string $module = '';

    public string $viewName = '';

    public function mount(): void
    {
        $this->reconcileVelmModuleUi($this->module);

        if (in_array($this->presentationType(), ['list', 'kanban'], true)) {
            parent::mount();
        }
    }

    public function showListColumnsPanel(): bool
    {
        return $this->presentationType() !== 'kanban';
    }

    public function showListGroupByPanel(): bool
    {
        if ($this->presentationType() !== 'kanban') {
            return true;
        }

        return $this->kanbanArchGroupBy() !== null;
    }

    /**
     * @return list<array{name: string, label: string, filter_kind: string, group_kind: string, comodel: ?string, visible_default: bool}>
     */
    public function listHeaders(): array
    {
        if ($this->presentationType() === 'kanban') {
            return $this->kanbanToolbarHeaders();
        }

        return app(ListColumnHeaders::class)->fromArch($this->arch(), app(Environment::class));
    }

    public function render()
    {
        return match ($this->presentationType()) {
            'list' => view('velm-ui::list.page'),
            'kanban' => view('velm-admin::pages.analytics.kanban'),
            'graph' => view('velm-admin::pages.analytics.graph'),
            'pivot' => view('velm-admin::pages.analytics.pivot'),
            default => abort(404, 'Unsupported stored view type: '.$this->presentationType()),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function kanbanBoard(): array
    {
        $board = app(KanbanBoardBuilder::class)->build(
            $this->arch(),
            app(Environment::class),
            $this->kanbanQueryArch(),
            $this->listQuery(),
            $this->kanbanArchGroupBy(),
        );

        if ($board['grouped']) {
            foreach ($board['columns'] as &$column) {
                foreach ($column['cards'] as &$card) {
                    $card['open_url'] = $this->cardOpenUrl((int) $card['id']);
                }
            }

            unset($column, $card);
        } else {
            foreach ($board['cards'] as &$card) {
                $card['open_url'] = $this->cardOpenUrl((int) $card['id']);
            }

            unset($card);

            $board = $this->paginateFlatKanbanBoard($board);
        }

        if ($board['grouped']) {
            $board['paginator'] = null;
        }

        return $board;
    }

    public function listPaginationStyle(): string
    {
        if ($this->presentationType() === 'kanban') {
            $listView = $this->arch()['list_view'] ?? null;

            if (is_string($listView) && $listView !== '') {
                try {
                    $listArch = app(ViewRegistry::class)->arch(
                        app(Environment::class),
                        $this->module,
                        $listView,
                    );
                    $archStyle = $listArch['pagination'] ?? null;

                    if (is_string($archStyle) && $archStyle !== '') {
                        return ListPagination::resolveStyle($archStyle);
                    }
                } catch (\Throwable) {
                    // Fall back to configured default when the sibling list is unavailable.
                }
            }
        }

        return parent::listPaginationStyle();
    }

    /**
     * @return array<string, mixed>
     */
    public function graphData(): array
    {
        return app(GraphDataBuilder::class)->build($this->arch(), app(Environment::class));
    }

    /**
     * @return array<string, mixed>
     */
    public function graphToolbarConfig(): array
    {
        $arch = $this->arch();
        $graph = $this->graphData();
        $catalog = app(ViewFieldCatalog::class)->forModel((string) $arch['model'], app(Environment::class));

        return [
            'model' => (string) $arch['model'],
            'module' => $this->module,
            'view' => $this->viewName,
            'initGroupby' => $graph['group_by'],
            'initMeasure' => $graph['measure'],
            'initChart' => $graph['chart'],
            'initLabels' => $graph['labels'],
            'initValues' => $graph['values'],
            'initMeasureLabel' => $graph['measure_label'],
            'groupable' => $catalog['groupable'],
            'measurable' => $catalog['measurable'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function pivotData(): array
    {
        return app(PivotDataBuilder::class)->build($this->arch(), app(Environment::class));
    }

    /**
     * @return array<string, mixed>
     */
    public function pivotToolbarConfig(): array
    {
        $arch = $this->arch();
        $catalog = app(ViewFieldCatalog::class)->forModel((string) $arch['model'], app(Environment::class));
        $pivot = ArchNormalizer::normalize($arch, 'pivot');

        return [
            'model' => (string) $arch['model'],
            'module' => $this->module,
            'view' => $this->viewName,
            'initRowGroupby' => implode(',', $pivot['rows'] ?? []),
            'initColGroupby' => implode(',', $pivot['cols'] ?? []),
            'initMeasures' => implode(',', $pivot['measures'] ?? ['__count']),
            'groupable' => $catalog['groupable'],
            'measurable' => $catalog['measurable'],
            'initial' => $this->pivotData(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function pivotGrid(): array
    {
        return app(PivotGridBuilder::class)->build($this->arch(), app(Environment::class));
    }

    /**
     * @return array<string, mixed>
     */
    protected function kanbanQueryArch(): array
    {
        $arch = $this->arch();
        $listView = $arch['list_view'] ?? null;

        if (is_string($listView) && $listView !== '') {
            return app(ViewRegistry::class)->arch(app(Environment::class), $this->module, $listView);
        }

        return [
            'model' => $arch['model'],
            'fields' => array_map(
                static fn (array $header): array => ['name' => $header['name']],
                $this->kanbanToolbarHeaders(),
            ),
        ];
    }

    /**
     * @return list<array{name: string, label: string, filter_kind: string, group_kind: string, comodel: ?string, visible_default: bool}>
     */
    protected function kanbanToolbarHeaders(): array
    {
        $arch = $this->arch();
        $listView = $arch['list_view'] ?? null;
        $env = app(Environment::class);

        if (is_string($listView) && $listView !== '') {
            $listArch = app(ViewRegistry::class)->arch($env, $this->module, $listView);

            return app(ListColumnHeaders::class)->fromArch($listArch, $env);
        }

        return app(ListColumnHeaders::class)->fromModel((string) ($arch['model'] ?? ''), $env);
    }

    protected function cardOpenUrl(int $recordId): ?string
    {
        return $this->openRecordUrl($recordId) ?? $this->editRecordUrl($recordId);
    }

    protected function listViewUrl(): ?string
    {
        $arch = $this->arch();
        $listView = $arch['list_view'] ?? null;

        if (! is_string($listView) || $listView === '') {
            return null;
        }

        return StoredViewRoutes::viewPageUrl($this->module, $listView);
    }

    protected function velmViewModule(): string
    {
        return $this->module;
    }

    protected function velmViewName(): string
    {
        return $this->viewName;
    }

    protected function presentationType(): string
    {
        return StoredViewRoutes::presentationType($this->module, $this->viewName);
    }

    protected function kanbanArchGroupBy(): ?string
    {
        $groupBy = (string) ($this->arch()['group_by'] ?? '');

        return $groupBy !== '' ? $groupBy : null;
    }

    /**
     * @param  array<string, mixed>  $board
     * @return array<string, mixed>
     */
    private function paginateFlatKanbanBoard(array $board): array
    {
        $cards = is_array($board['cards'] ?? null) ? $board['cards'] : [];
        $total = count($cards);
        $perPage = ListPageSize::effectivePerPage($this->listPerPage, $total);
        $page = ListPageSize::isAll($this->listPerPage) ? 1 : max(1, $this->getPage());
        $slice = ListPageSize::isAll($this->listPerPage)
            ? $cards
            : array_slice($cards, ($page - 1) * $perPage, $perPage);

        $board['paginator'] = new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'pageName' => 'page'],
        );
        $board['cards'] = $board['paginator']->items();

        return $board;
    }
}
