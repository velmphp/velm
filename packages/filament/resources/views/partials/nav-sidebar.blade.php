@php
    use Velm\Filament\Support\MenuLinkResolver;
@endphp

<nav class="flex-1 space-y-0.5 overflow-y-auto px-2 py-3">
    @foreach ($menu as $nav)
        @if (! empty($nav['children']) && empty($nav['href']))
            <p @class([
                'truncate px-3 pb-1 text-xs font-semibold uppercase tracking-wide text-body-subtle',
                'pt-4' => ! $loop->first,
                'pt-1' => $loop->first,
            ])>
                {{ $nav['label'] }}
            </p>

            @foreach ($nav['children'] as $child)
                @if (! empty($child['children']))
                    <details
                        class="group"
                        name="pv-sidebar-accordion"
                        @if ($child['active'] ?? false) open @endif
                    >
                        <summary
                            @class([
                                'nav-item group/summary flex cursor-pointer select-none items-center gap-2.5 rounded-md px-3 py-2 text-sm [&::-webkit-details-marker]:hidden',
                                'nav-active' => ($child['active'] ?? false) && empty($child['href']),
                            ])
                        >
                            <span class="flex-1 truncate">{{ $child['label'] }}</span>
                            <x-filament::icon
                                icon="heroicon-m-chevron-down"
                                class="h-3 w-3 text-body-subtle transition group-open:rotate-180"
                            />
                        </summary>
                        <ul class="mt-1 ms-1 space-y-0.5 border-s border-default ps-3">
                            @foreach ($child['children'] as $grandchild)
                                @php $grandUrl = MenuLinkResolver::url($grandchild['href'] ?? null); @endphp
                                <li>
                                    <a
                                        href="{{ $grandUrl ?? '#' }}"
                                        @class([
                                            'nav-item flex items-center gap-2.5 rounded-md px-3 py-1.5 text-sm',
                                            'nav-active' => $grandchild['active'] ?? false,
                                        ])
                                        @click="sidebarOpen = false"
                                    >
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
                        @class([
                            'nav-item flex items-center gap-2.5 rounded-md px-3 py-2 text-sm',
                            'nav-active' => $child['active'] ?? false,
                        ])
                        @click="sidebarOpen = false"
                    >
                        {{ $child['label'] }}
                    </a>
                @endif
            @endforeach
        @else
            @php $navUrl = MenuLinkResolver::url($nav['href'] ?? null); @endphp
            <a
                href="{{ $navUrl ?? '#' }}"
                @class([
                    'nav-item flex items-center gap-2.5 rounded-md px-3 py-2 text-sm',
                    'nav-active' => $nav['active'] ?? false,
                ])
                @click="sidebarOpen = false"
            >
                {{ $nav['label'] }}
            </a>
        @endif
    @endforeach
</nav>
