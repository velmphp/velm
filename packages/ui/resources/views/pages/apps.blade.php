@php
    $summary = $this->catalogSummary();
    $catalog = $this->moduleCatalog();
@endphp

<div
    class="space-y-4"
    x-data="velmAppsCatalogHost"
    data-velm-breadcrumb-trail="{{ $this->velmBreadcrumbTrailJson() }}"
    data-velm-nav-label="{{ $this->velmNavLabel() }}"
>
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-heading">{{ $this->getTitle() }}</h1>
            <p class="mt-1 text-sm text-body-subtle">
                {{ $summary['total'] }} modules ·
                {{ $summary['installed'] }} installed ·
                {{ $summary['to_upgrade'] }} to upgrade ·
                {{ $summary['needs_sync'] }} sync pending ·
                {{ $summary['uninstalled'] }} not installed
            </p>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <div class="relative min-w-[200px] max-w-md flex-1">
            <svg class="pointer-events-none absolute top-1/2 left-2.5 h-4 w-4 -translate-y-1/2 text-body-subtle" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803 7.5 7.5 0 0015.803 15.803z"/>
            </svg>
            <input
                type="search"
                x-ref="searchInput"
                x-model="query"
                @input="applyFilters()"
                placeholder="{{ __('Search modules…') }}"
                autocomplete="off"
                class="w-full rounded-md border border-default bg-neutral-primary py-1.5 pr-3 pl-8 text-sm text-body placeholder:text-body-subtle focus:border-fg-brand focus:ring-2 focus:ring-fg-brand/40 focus:outline-none"
            />
        </div>

        <span class="text-xs text-body-subtle" x-text="`${visibleCount} of {{ count($catalog) }}`"></span>

        <button
            type="button"
            @click="clearFilters()"
            class="text-xs text-fg-brand hover:underline"
            x-show="hasActiveFilters"
            x-cloak
        >
            {{ __('Clear filters') }}
        </button>
    </div>

    <div
        x-show="visibleCount === 0 && {{ count($catalog) }}"
        x-cloak
        class="rounded-lg border border-default bg-neutral-primary p-5 text-center"
    >
        <p class="text-sm text-body-subtle">{{ __('No modules match the current filters.') }}</p>
        <button
            type="button"
            @click="clearFilters()"
            class="mt-2 text-xs text-fg-brand hover:underline"
        >
            {{ __('Clear filters') }}
        </button>
    </div>

    @if ($catalog === [])
        <div class="rounded-lg border border-default bg-neutral-primary p-6 text-center">
            <p class="text-sm text-body-subtle">
                {{ __('No modules discovered. Configure') }}
                <code class="text-xs">velm.addon_paths</code>
                {{ __('in config.') }}
            </p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($catalog as $app)
                <article
                    data-velm-app
                    data-velm-app-state="{{ $app['state'] }}"
                    data-velm-app-category="{{ $app['category'] }}"
                    data-velm-app-haystack="{{ strtolower(implode(' ', [$app['name'], $app['display_name'], $app['summary'], $app['description'], $app['author'], $app['category']])) }}"
                    class="flex min-h-0 flex-col overflow-hidden rounded-xl border border-default bg-neutral-primary shadow-sm transition-all hover:border-fg-brand/40 hover:shadow-md"
                >
                    <a
                        href="{{ \Velm\Admin\Pages\AppsDetailPage::getUrl(['name' => $app['name']]) }}"
                        wire:navigate
                        class="group flex min-h-0 flex-1 flex-col gap-2 p-3.5 pb-2"
                    >
                        <div class="flex items-start justify-between gap-2">
                            @include('velm-ui::pages.apps.partials.icon', ['app' => $app, 'size' => 'w-9 h-9', 'iconSize' => 'w-5 h-5'])
                            <div class="flex min-w-0 max-w-[45%] shrink-0 flex-col items-end gap-0.5">
                                @include('velm-ui::pages.apps.partials.state-badge', ['app' => $app])
                                <span class="w-full truncate text-right font-mono text-2xs text-body-subtle" title="{{ $app['name'] }}">{{ $app['name'] }}</span>
                            </div>
                        </div>
                        <h4 class="truncate text-sm font-semibold text-heading group-hover:text-fg-brand">{{ $app['display_name'] }}</h4>
                        <p class="truncate text-xs text-body-subtle">{{ $app['category'] }}</p>
                        @if (($app['summary'] ?? '') !== '')
                            <p class="line-clamp-2 text-xs leading-relaxed text-body">{{ $app['summary'] }}</p>
                        @endif
                    </a>
                    <footer class="border-t border-default px-3 pt-2 pb-3" @click.stop>
                        @include('velm-ui::pages.apps.partials.actions', ['app' => $app, 'compact' => true])
                    </footer>
                </article>
            @endforeach
        </div>
    @endif
</div>
