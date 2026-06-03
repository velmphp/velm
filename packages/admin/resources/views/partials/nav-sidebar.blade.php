@php
    use Velm\Admin\Support\MenuLinkResolver;
@endphp

<nav class="pv-sidebar-nav flex-1 overflow-y-auto px-2 py-3" aria-label="{{ __('Main navigation') }}">
    @foreach ($menu as $nav)
        @if (! empty($nav['children']) && empty($nav['href']))
            <section class="pv-sidebar-section">
                <h2 class="pv-sidebar-section-label">
                    {{ $nav['label'] }}
                </h2>

                <div class="pv-sidebar-links">
                    @foreach ($nav['children'] as $child)
                        @if (! empty($child['children']))
                            <details
                                class="group"
                                name="pv-sidebar-accordion"
                                @if ($child['active'] ?? false) open @endif
                            >
                                <summary
                                    @class([
                                        'nav-item group/summary flex cursor-pointer select-none items-center gap-2.5 rounded-md px-3 py-2 text-sm [&::-webkit-details-marker]:hidden marker:hidden',
                                        'nav-active' => ($child['active'] ?? false) && empty($child['href']),
                                    ])
                                >
                                    @include('velm-admin::partials.nav-menu-icon', ['icon' => $child['icon'] ?? null])
                                    <span class="flex-1 truncate">{{ $child['label'] }}</span>
                                    <svg
                                        class="h-3 w-3 shrink-0 text-body-subtle transition-transform group-open:rotate-180"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                        stroke-width="2.5"
                                        aria-hidden="true"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </summary>
                                <ul class="pv-sidebar-nested mt-1 ml-1 border-l border-default pl-3">
                                    @foreach ($child['children'] as $grandchild)
                                        @php $grandUrl = MenuLinkResolver::url($grandchild['href'] ?? null); @endphp
                                        <li>
                                            <a
                                                href="{{ $grandUrl ?? '#' }}"
                                                data-nav-link
                                                @class([
                                                    'nav-item flex items-center gap-2.5 rounded-md px-3 py-2 text-sm',
                                                    'nav-active' => $grandchild['active'] ?? false,
                                                ])
                                                @click="sidebarOpen = false"
                                            >
                                                @include('velm-admin::partials.nav-menu-icon', ['icon' => $grandchild['icon'] ?? null])
                                                {{ $grandchild['label'] }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </details>
                        @else
                            @php $childUrl = MenuLinkResolver::url($child['href'] ?? null); @endphp
                            <a
                                href="{{ $childUrl ?? '#' }}"
                                data-nav-link
                                @class([
                                    'nav-item flex items-center gap-2.5 rounded-md px-3 py-2 text-sm',
                                    'nav-active' => $child['active'] ?? false,
                                ])
                                @click="sidebarOpen = false"
                            >
                                @include('velm-admin::partials.nav-menu-icon', ['icon' => $child['icon'] ?? null])
                                {{ $child['label'] }}
                            </a>
                        @endif
                    @endforeach
                </div>
            </section>
        @else
            <section class="pv-sidebar-section">
                <div class="pv-sidebar-links">
                    @php $navUrl = MenuLinkResolver::url($nav['href'] ?? null); @endphp
                    <a
                        href="{{ $navUrl ?? '#' }}"
                        data-nav-link
                        @class([
                            'nav-item flex items-center gap-2.5 rounded-md px-3 py-2 text-sm',
                            'nav-active' => $nav['active'] ?? false,
                        ])
                        @click="sidebarOpen = false"
                    >
                        @include('velm-admin::partials.nav-menu-icon', ['icon' => $nav['icon'] ?? null])
                        {{ $nav['label'] }}
                    </a>
                </div>
            </section>
        @endif
    @endforeach

    @if (($velmMenu['menu_layout'] ?? '') !== 'apps_catalog')
        <div class="pv-sidebar-links mt-2 space-y-0.5">
            @include('velm-admin::partials.nav-apps-catalog-entry')
        </div>
    @endif
</nav>
