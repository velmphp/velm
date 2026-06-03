@php
    use Velm\Admin\Support\MenuLinkResolver;

    $secondary = $menu['menu_secondary'] ?? [];
    $activeRoot = $menu['menu_active_root'] ?? null;
@endphp

<nav
    class="pv-app-menu flex min-w-0 flex-1 flex-wrap items-center gap-1 overflow-visible"
    aria-label="{{ $activeRoot ? $activeRoot['label'].' menu' : 'App menu' }}"
    x-data="{ openKey: '' }"
    @click.outside="openKey = ''"
    @keydown.escape.window="openKey = ''"
>
    @if ($activeRoot && $secondary === [])
        <span class="max-w-[12rem] shrink-0 truncate text-sm font-semibold text-heading">
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
                        'flex items-center gap-1 rounded-md px-2.5 py-1.5 text-sm whitespace-nowrap',
                        'bg-brand-soft font-medium text-fg-brand-strong' => $item['active'] ?? false,
                        'text-body hover:bg-neutral-secondary hover:text-heading' => ! ($item['active'] ?? false),
                    ])
                    :aria-expanded="(openKey === '{{ $key }}').toString()"
                >
                    {{ $item['label'] }}
                    <svg
                        class="h-3 w-3 text-body-subtle transition-transform"
                        x-bind:class="{ 'rotate-180': openKey === '{{ $key }}' }"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        stroke-width="2.5"
                        aria-hidden="true"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
                <div
                    x-show="openKey === '{{ $key }}'"
                    x-cloak
                    x-transition
                    class="absolute left-0 top-full z-50 mt-1 min-w-[10rem] rounded-lg border border-default bg-neutral-primary py-1 shadow-lg"
                >
                    @foreach ($item['children'] as $child)
                        @php $childUrl = MenuLinkResolver::url($child['href'] ?? null); @endphp
                        <a
                            href="{{ $childUrl ?? '#' }}"
                            data-nav-link
                            @class([
                                'block px-3 py-1.5 text-sm whitespace-nowrap',
                                'bg-brand-softer font-medium text-fg-brand' => $child['active'] ?? false,
                                'text-body hover:bg-neutral-secondary hover:text-heading' => ! ($child['active'] ?? false),
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
                data-nav-link
                @class([
                    'shrink-0 rounded-md px-2.5 py-1.5 text-sm whitespace-nowrap',
                    'bg-brand-soft font-medium text-fg-brand-strong' => $item['active'] ?? false,
                    'text-body hover:bg-neutral-secondary hover:text-heading' => ! ($item['active'] ?? false),
                ])
            >
                {{ $item['label'] }}
            </a>
        @endif
    @endforeach
</nav>
