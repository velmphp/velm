@php($board = $this->kanbanBoard())

<div
    class="space-y-4"
    data-pv-form-shell
    data-velm-breadcrumb-trail="{{ $this->velmBreadcrumbTrailJson() }}"
    data-velm-nav-label="{{ $this->velmNavLabel() }}"
>
    @include('velm-ui::partials.breadcrumbs')

    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-lg font-semibold text-heading">{{ $this->getTitle() }}</h1>
        <div class="flex flex-wrap items-center gap-2">
            @include('velm-ui::partials.view-actions', ['actions' => $this->velmPageActions()])
            @if ($createUrl = $this->createPageUrl())
                <a
                    href="{{ $createUrl }}"
                    class="inline-flex items-center gap-1.5 rounded-md bg-fg-brand px-3 py-1.5 text-sm font-medium text-white transition-opacity hover:opacity-90"
                >
                    <x-velm-ui::icon icon="heroicon-o-plus" class="h-4 w-4" />
                    {{ __('New') }}
                </a>
            @endif
        </div>
    </div>

    @if (count($this->analyticsViewSwitcher()) > 1)
        @include('velm-admin::pages.analytics.partials.view-switcher')
    @endif

    @include('velm-admin::components.arch-list-toolbar')

    @if ($board['grouped'] && $board['columns'] === [])
        <div class="rounded-lg border border-default bg-surface p-6 text-sm text-body-subtle">
            {{ __('No records to display.') }}
        </div>
    @elseif (! $board['grouped'] && $board['cards'] === [])
        <div class="rounded-lg border border-default bg-surface p-6 text-sm text-body-subtle">
            {{ __('No records to display.') }}
        </div>
    @elseif ($board['grouped'])
        <div class="flex gap-4 overflow-x-auto pb-2">
            @foreach ($board['columns'] as $column)
                <section class="min-w-[18rem] flex-1 rounded-lg border border-default bg-surface-muted/40">
                    <header class="flex items-center justify-between border-b border-default px-3 py-2">
                        <h2 class="text-sm font-semibold text-heading">{{ $column['label'] }}</h2>
                        <span class="rounded-full bg-surface px-2 py-0.5 text-xs text-body-subtle">
                            {{ count($column['cards']) }}
                        </span>
                    </header>

                    <div class="space-y-2 p-2">
                        @include('velm-admin::pages.analytics.partials.kanban-cards', ['cards' => $column['cards']])
                    </div>
                </section>
            @endforeach
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @include('velm-admin::pages.analytics.partials.kanban-cards', ['cards' => $board['cards']])
        </div>

        @include('velm-ui::pagination.footer', ['paginator' => $board['paginator'] ?? null])
    @endif
</div>
