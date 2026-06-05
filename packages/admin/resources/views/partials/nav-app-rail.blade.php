@php
    use Velm\Admin\Support\MenuLinkResolver;

    $roots = $menu['menu_roots'] ?? [];
    $activeIndex = $menu['menu_active_root_index'] ?? null;
@endphp

<nav class="pv-sidebar-nav pv-sidebar-nav--rail flex-1 overflow-y-auto px-2 py-3" aria-label="{{ __('Applications') }}">
    <div class="pv-sidebar-links space-y-0.5">
        @include('velm-admin::partials.nav-dashboard-entry')

        @foreach ($roots as $nav)
            @php
                $href = MenuLinkResolver::url($nav['nav_href'] ?? null);
                $isActive = $activeIndex !== null && ($nav['root_index'] ?? null) === $activeIndex;
            @endphp

            <a
                href="{{ $href ?? '#' }}"
                data-nav-link
                @class([
                    'nav-item flex items-center gap-2.5 rounded-md px-3 py-2 text-sm',
                    'nav-active' => $isActive,
                ])
                @if (! $href) aria-disabled="true" tabindex="-1" @endif
                @click="sidebarOpen = false"
            >
                @include('velm-admin::partials.nav-menu-icon', ['icon' => $nav['icon'] ?? null])
                <span class="truncate">{{ $nav['label'] }}</span>
            </a>
        @endforeach

        @include('velm-admin::partials.nav-apps-catalog-entry')
    </div>
</nav>
