@php
    $board = $this->dashboardBoard();
    $gridClass = 'grid grid-cols-1 gap-4';

    if (($board['columns'] ?? 2) >= 3) {
        $gridClass .= ' md:grid-cols-2 xl:grid-cols-3';
    } elseif (($board['columns'] ?? 2) >= 2) {
        $gridClass .= ' md:grid-cols-2';
    }
@endphp

<div
    class="space-y-4"
    data-velm-breadcrumb-trail="{{ $this->velmBreadcrumbTrailJson() }}"
    data-velm-nav-label="{{ $this->velmNavLabel() }}"
>
    @include('velm-ui::partials.breadcrumbs')

    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-lg font-semibold text-heading">{{ $this->getTitle() }}</h1>
        @if ($listUrl = $this->listViewUrl())
            <a
                href="{{ $listUrl }}"
                wire:navigate
                class="inline-flex items-center gap-1.5 rounded-md border border-default bg-surface px-3 py-1.5 text-sm font-medium text-body transition-colors hover:bg-neutral-secondary"
            >
                <x-velm-ui::icon icon="heroicon-o-list-bullet" class="h-4 w-4" />
                {{ __('Open list') }}
            </a>
        @endif
    </div>

    @if (count($this->analyticsViewSwitcher()) > 1)
        @include('velm-admin::pages.analytics.partials.view-switcher')
    @endif

    @if ($board['widgets'] === [])
        <div class="rounded-lg border border-dashed border-default bg-neutral-primary px-6 py-10 text-center">
            <p class="text-sm font-medium text-heading">{{ __('No dashboard widgets available') }}</p>
            <p class="mt-1 text-sm text-body-subtle">
                {{ __('Widgets are hidden when you lack read access to the underlying models.') }}
            </p>
        </div>
    @else
        <div class="{{ $gridClass }}">
            @foreach ($board['widgets'] as $widget)
                <div wire:key="dashboard-widget-{{ $widget['id'] }}" class="min-w-0 {{ $widget['span_class'] ?? 'md:col-span-1' }}">
                    @include($widget['view'], ['widget' => $widget])
                </div>
            @endforeach
        </div>
    @endif
</div>
