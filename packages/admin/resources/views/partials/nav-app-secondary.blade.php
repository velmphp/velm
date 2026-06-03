@php
    use Velm\Admin\Support\MenuLinkResolver;

    $secondary = $menu['menu_secondary'] ?? [];
    $activeRoot = $menu['menu_active_root'] ?? null;
@endphp

@if ($activeRoot && $secondary !== [])
    <nav
        class="pv-sidebar-nav pv-sidebar-nav--secondary hidden shrink-0 border-t border-default/60 px-2 py-3 md:block"
        aria-label="{{ $activeRoot['label'].' '.__('menu') }}"
    >
        <p class="px-3 pb-1 text-2xs font-semibold uppercase tracking-wider text-body-subtle">
            {{ $activeRoot['label'] }}
        </p>
        <div class="pv-sidebar-links space-y-0.5">
            @foreach ($secondary as $item)
                @php $url = MenuLinkResolver::url($item['href'] ?? null); @endphp
                <a
                    href="{{ $url ?? '#' }}"
                    data-nav-link
                    @class([
                        'nav-item flex items-center gap-2.5 rounded-md px-3 py-2 text-sm',
                        'nav-active' => $item['active'] ?? false,
                    ])
                    @click="sidebarOpen = false"
                >
                    @include('velm-admin::partials.nav-menu-icon', ['icon' => $item['icon'] ?? null])
                    <span class="truncate">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>
    </nav>
@endif
