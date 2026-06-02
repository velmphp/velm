@php
    use Velm\Filament\Support\MenuLinkResolver;
@endphp

<nav class="flex-1 space-y-0.5 overflow-y-auto px-2 py-3">
    @foreach ($menu as $nav)
        @if (! empty($nav['children']) && empty($nav['href']))
            <p @class([
                'truncate px-3 pb-1 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400',
                'pt-4' => ! $loop->first,
                'pt-1' => $loop->first,
            ])>
                {{ $nav['label'] }}
            </p>

            @foreach ($nav['children'] as $child)
                @if (! empty($child['children']))
                    <details
                        class="group"
                        name="velm-sidebar-accordion"
                        @if ($child['active'] ?? false) open @endif
                    >
                        <summary
                            @class([
                                'flex cursor-pointer select-none items-center gap-2.5 rounded-md px-3 py-2 text-sm transition [&::-webkit-details-marker]:hidden',
                                'bg-amber-50 font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' => $child['active'] ?? false,
                                'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-white' => ! ($child['active'] ?? false),
                            ])
                        >
                            <span class="flex-1 truncate">{{ $child['label'] }}</span>
                            <x-filament::icon icon="heroicon-m-chevron-down" class="h-3 w-3 transition group-open:rotate-180" />
                        </summary>
                        <ul class="mt-1 ms-1 space-y-0.5 border-s border-gray-200 ps-3 dark:border-white/10">
                            @foreach ($child['children'] as $grandchild)
                                @php $grandUrl = MenuLinkResolver::url($grandchild['href'] ?? null); @endphp
                                <li>
                                    <a
                                        href="{{ $grandUrl ?? '#' }}"
                                        @class([
                                            'flex items-center gap-2.5 rounded-md px-3 py-1.5 text-sm transition',
                                            'bg-amber-50 font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' => $grandchild['active'] ?? false,
                                            'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-white' => ! ($grandchild['active'] ?? false),
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
                            'flex items-center gap-2.5 rounded-md px-3 py-2 text-sm transition',
                            'bg-amber-50 font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' => $child['active'] ?? false,
                            'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-white' => ! ($child['active'] ?? false),
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
                    'flex items-center gap-2.5 rounded-md px-3 py-2 text-sm transition',
                    'bg-amber-50 font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' => $nav['active'] ?? false,
                    'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-white' => ! ($nav['active'] ?? false),
                ])
                @click="sidebarOpen = false"
            >
                {{ $nav['label'] }}
            </a>
        @endif
    @endforeach
</nav>
