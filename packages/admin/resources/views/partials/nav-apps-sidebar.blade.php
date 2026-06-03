@php
    use Velm\Admin\Pages\AppsPage;

    $summary = $menu['apps_summary'] ?? ['total' => 0, 'installed' => 0, 'to_upgrade' => 0, 'uninstalled' => 0];
    $states = $menu['apps_states'] ?? [];
    $categories = $menu['apps_categories'] ?? [];
    $activeModule = $menu['apps_active_module'] ?? null;
    $catalogUrl = $menu['apps_catalog_url'] ?? AppsPage::getUrl();
    $workspaceRoots = $menu['workspace_roots'] ?? [];
    $onCatalog = $activeModule === null;
@endphp

<nav class="pv-sidebar-nav flex-1 overflow-y-auto px-2 py-3" aria-label="{{ __('Apps catalog') }}">
    <section class="pv-sidebar-section pv-sidebar-section--first">
        <h2 class="pv-sidebar-section-label">{{ __('Catalog') }}</h2>
        <div class="pv-sidebar-links">
            <a
                href="{{ $catalogUrl }}"
                wire:navigate
                data-nav-link
                @class([
                    'nav-item flex items-center gap-2.5 rounded-md px-3 py-2 text-sm',
                    'nav-active' => $onCatalog,
                ])
                @click="sidebarOpen = false"
            >
                <x-velm-ui::icon icon="heroicon-o-squares-2x2" class="h-4 w-4 shrink-0" />
                <span class="truncate">{{ __('Browse modules') }}</span>
            </a>
        </div>
    </section>

    @if ($onCatalog)
        <section class="pv-sidebar-section">
            <h2 class="pv-sidebar-section-label">{{ __('Status') }}</h2>
            <div class="pv-sidebar-links space-y-0.5">
                @foreach ($states as $state)
                    <button
                        type="button"
                        @click="$store.velmAppsCatalog.setStateFilter(@js($state['key']))"
                        :class="$store.velmAppsCatalog.stateFilter === @js($state['key'])
                            ? 'nav-active'
                            : ''"
                        class="nav-item flex w-full cursor-pointer items-center justify-between gap-2 rounded-md px-3 py-2 text-left text-sm"
                    >
                        <span class="truncate">{{ __($state['label']) }}</span>
                        <span class="shrink-0 font-mono text-2xs text-body-subtle">{{ $state['count'] }}</span>
                    </button>
                @endforeach
            </div>
        </section>

        @if ($categories !== [])
            <section class="pv-sidebar-section">
                <h2 class="pv-sidebar-section-label">{{ __('Category') }}</h2>
                <div class="pv-sidebar-links space-y-0.5">
                    <button
                        type="button"
                        @click="$store.velmAppsCatalog.setCategoryFilter('')"
                        :class="$store.velmAppsCatalog.categoryFilter === '' ? 'nav-active' : ''"
                        class="nav-item flex w-full cursor-pointer items-center rounded-md px-3 py-2 text-left text-sm"
                    >
                        {{ __('All categories') }}
                    </button>
                    @foreach ($categories as $category)
                        <button
                            type="button"
                            @click="$store.velmAppsCatalog.setCategoryFilter(@js($category))"
                            :class="$store.velmAppsCatalog.categoryFilter === @js($category) ? 'nav-active' : ''"
                            class="nav-item flex w-full cursor-pointer items-center rounded-md px-3 py-2 text-left text-sm"
                        >
                            <span class="truncate">{{ $category }}</span>
                        </button>
                    @endforeach
                </div>
            </section>
        @endif
    @endif

    @if ($workspaceRoots !== [])
        <section class="pv-sidebar-section">
            <h2 class="pv-sidebar-section-label">{{ __('Open app') }}</h2>
            <div class="pv-sidebar-links">
                @foreach ($workspaceRoots as $root)
                    <a
                        href="{{ $root['href'] }}"
                        wire:navigate
                        data-nav-link
                        class="nav-item flex items-center gap-2.5 rounded-md px-3 py-2 text-sm"
                        @click="sidebarOpen = false"
                    >
                        @include('velm-admin::partials.nav-menu-icon', ['icon' => $root['icon'] ?? null])
                        <span class="truncate">{{ $root['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</nav>
