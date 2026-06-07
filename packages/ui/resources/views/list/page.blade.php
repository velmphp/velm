<div
    class="space-y-4"
    data-velm-breadcrumb-trail="{{ $this->velmBreadcrumbTrailJson() }}"
    data-velm-nav-label="{{ $this->velmNavLabel() }}"
>
    @include('velm-ui::partials.breadcrumbs')

    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-lg font-semibold text-heading">{{ $this->getTitle() }}</h1>
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

    @if (count($this->analyticsViewSwitcher()) > 1)
        @include('velm-admin::pages.analytics.partials.view-switcher')
    @endif

    @include('velm-admin::components.arch-list-toolbar')

    @include('velm-ui::list.table')
</div>
