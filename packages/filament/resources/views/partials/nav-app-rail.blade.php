@php
    use Velm\Filament\Support\MenuLinkResolver;

    $roots = $menu['menu_roots'] ?? [];
    $activeIndex = $menu['menu_active_root_index'] ?? null;
@endphp

<nav class="flex-1 space-y-0.5 overflow-y-auto px-2 py-3" aria-label="{{ __('Applications') }}">
    @foreach ($roots as $nav)
        @php
            $href = MenuLinkResolver::url($nav['nav_href'] ?? null);
            $isActive = $activeIndex !== null && ($nav['root_index'] ?? null) === $activeIndex;
            $icon = $nav['icon'] ?? null;
            $heroicon = $icon ? (str_contains((string) $icon, 'heroicon') ? $icon : "heroicon-o-{$icon}") : null;
        @endphp

        <a
            href="{{ $href ?? '#' }}"
            @class([
                'flex items-center gap-2.5 rounded-md px-3 py-2 text-sm transition',
                'bg-primary-50 font-semibold text-primary-600 dark:bg-primary-500/10 dark:text-primary-400' => $isActive,
                'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-white' => ! $isActive,
            ])
            @if (! $href) aria-disabled="true" tabindex="-1" @endif
            @click="sidebarOpen = false"
        >
            @if ($heroicon)
                <x-filament::icon :icon="$heroicon" class="h-4 w-4 shrink-0" />
            @endif
            <span class="truncate">{{ $nav['label'] }}</span>
        </a>
    @endforeach
</nav>
