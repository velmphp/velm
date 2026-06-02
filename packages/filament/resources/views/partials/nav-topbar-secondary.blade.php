@php
    use Velm\Filament\Support\MenuLinkResolver;

    $secondary = $menu['menu_secondary'] ?? [];
    $activeRoot = $menu['menu_active_root'] ?? null;
@endphp

<nav
    class="flex min-w-0 flex-1 flex-wrap items-center gap-1"
    aria-label="{{ $activeRoot ? $activeRoot['label'].' menu' : 'App menu' }}"
    x-data="{ openKey: '' }"
    @click.outside="openKey = ''"
    @keydown.escape.window="openKey = ''"
>
    @if ($activeRoot && $secondary === [])
        <span class="max-w-[12rem] truncate text-sm font-semibold text-gray-900 dark:text-white">
            {{ $activeRoot['label'] }}
        </span>
    @endif

    @foreach ($secondary as $item)
        @if (! empty($item['children']))
            @php $key = 'sec-'.$loop->index; @endphp
            <div class="relative shrink-0">
                <button
                    type="button"
                    @click="openKey = openKey === '{{ $key }}' ? '' : '{{ $key }}'"
                    @class([
                        'flex items-center gap-1 rounded-md px-2.5 py-1.5 text-sm whitespace-nowrap transition',
                        'bg-primary-50 font-medium text-primary-600 dark:bg-primary-500/10 dark:text-primary-400' => $item['active'] ?? false,
                        'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-white' => ! ($item['active'] ?? false),
                    ])
                    :aria-expanded="(openKey === '{{ $key }}').toString()"
                >
                    {{ $item['label'] }}
                    <x-filament::icon
                        icon="heroicon-m-chevron-down"
                        class="h-3 w-3 transition"
                        x-bind:class="{ 'rotate-180': openKey === '{{ $key }}' }"
                    />
                </button>
                <div
                    x-show="openKey === '{{ $key }}'"
                    x-cloak
                    x-transition
                    class="absolute start-0 top-full z-50 mt-1 min-w-40 rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-white/10 dark:bg-gray-900"
                >
                    @foreach ($item['children'] as $child)
                        @php $childUrl = MenuLinkResolver::url($child['href'] ?? null); @endphp
                        <a
                            href="{{ $childUrl ?? '#' }}"
                            @class([
                                'block px-3 py-1.5 text-sm whitespace-nowrap transition',
                                'bg-primary-50 font-medium text-primary-600 dark:bg-primary-500/10 dark:text-primary-400' => $child['active'] ?? false,
                                'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-white' => ! ($child['active'] ?? false),
                            ])
                        >
                            {{ $child['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @else
            @php $url = MenuLinkResolver::url($item['href'] ?? null); @endphp
            <a
                href="{{ $url ?? '#' }}"
                @class([
                    'shrink-0 rounded-md px-2.5 py-1.5 text-sm whitespace-nowrap transition',
                    'bg-primary-50 font-medium text-primary-600 dark:bg-primary-500/10 dark:text-primary-400' => $item['active'] ?? false,
                    'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-white' => ! ($item['active'] ?? false),
                ])
            >
                {{ $item['label'] }}
            </a>
        @endif
    @endforeach
</nav>
