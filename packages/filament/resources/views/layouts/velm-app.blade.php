@php
    use Filament\Support\Facades\FilamentView;
    use Filament\View\PanelsRenderHook;

    $livewire ??= null;
    $menu = $velmMenu ?? ['menu' => [], 'menu_layout' => 'apps'];
    $layoutMode = $menu['menu_layout'] ?? 'apps';
    $renderHookScopes = $livewire?->getRenderHookScopes();
    $maxContentWidth ??= filament()->getMaxContentWidth();
@endphp

<x-filament-panels::layout.base :livewire="$livewire">
    <div
        class="velm-shell flex min-h-[100dvh] w-full bg-gray-50 dark:bg-gray-950"
        x-data="{ sidebarOpen: false }"
    >
        <div
            x-cloak
            x-show="sidebarOpen"
            x-transition.opacity
            class="fixed inset-0 z-40 bg-gray-900/50 lg:hidden"
            @click="sidebarOpen = false"
        ></div>

        <aside
            class="fixed inset-y-0 start-0 z-50 flex w-56 flex-col border-e border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900
                   lg:static lg:z-auto lg:translate-x-0"
            :class="{ '-translate-x-full': ! sidebarOpen, 'translate-x-0': sidebarOpen }"
        >
            <div class="flex h-14 shrink-0 items-center border-b border-gray-200 px-4 dark:border-white/10">
                <x-filament-panels::logo class="h-8" />
            </div>

            @if ($layoutMode === 'sidebar')
                @include('velm-filament::partials.nav-sidebar', ['menu' => $menu['menu'] ?? []])
            @else
                @include('velm-filament::partials.nav-app-rail', ['menu' => $menu])
            @endif
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex min-h-14 shrink-0 items-center gap-3 border-b border-gray-200 bg-white px-4 dark:border-white/10 dark:bg-gray-900">
                <x-filament::icon-button
                    color="gray"
                    icon="heroicon-o-bars-3"
                    icon-size="lg"
                    label="{{ __('filament-panels::layout.actions.sidebar.expand.label') }}"
                    class="lg:hidden"
                    x-on:click="sidebarOpen = true"
                />

                @if ($layoutMode === 'apps')
                    @include('velm-filament::partials.nav-topbar-secondary', ['menu' => $menu])
                @endif

                <div class="ms-auto flex items-center gap-2">
                    @if (filament()->auth()->check() && filament()->hasUserMenu())
                        @livewire(\Filament\Livewire\SimpleUserMenu::class)
                    @endif
                </div>
            </header>

            <main @class([
                'fi-main flex-1 overflow-x-auto p-4 md:p-6',
                is_string($maxContentWidth) ? "fi-width-{$maxContentWidth}" : null,
            ])>
                {{ FilamentView::renderHook(PanelsRenderHook::CONTENT_START, scopes: $renderHookScopes) }}

                {{ $slot }}

                {{ FilamentView::renderHook(PanelsRenderHook::CONTENT_END, scopes: $renderHookScopes) }}
            </main>
        </div>
    </div>
</x-filament-panels::layout.base>
