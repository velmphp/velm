@php
    use Filament\Support\Facades\FilamentView;
    use Filament\View\PanelsRenderHook;

    $livewire ??= null;
    $menu = $velmMenu ?? ['menu' => [], 'menu_layout' => 'apps'];
    $layoutMode = $menu['menu_layout'] ?? 'apps';
    $renderHookScopes = $livewire?->getRenderHookScopes();
    $maxContentWidth ??= filament()->getMaxContentWidth();
    $activeRoot = $menu['menu_active_root'] ?? null;
@endphp

<x-filament-panels::layout.base :livewire="$livewire">
    <div
        class="velm-shell flex min-h-screen w-full bg-neutral-secondary font-sans text-body antialiased md:flex-row"
        x-data="{ sidebarOpen: false }"
    >
        <div
            x-cloak
            x-show="sidebarOpen"
            x-transition.opacity
            class="velm-shell-backdrop fixed inset-0 z-20 md:hidden"
            @click="sidebarOpen = false"
        ></div>

        <aside
            class="fixed inset-y-0 start-0 z-30 flex w-64 min-h-screen flex-shrink-0 flex-col bg-neutral-secondary transition-transform duration-200 ease-in-out md:relative md:translate-x-0"
            :class="{ '-translate-x-full': ! sidebarOpen, 'translate-x-0': sidebarOpen }"
        >
            <div class="velm-shell-sidebar-header relative flex shrink-0 items-center overflow-hidden border-b border-default/60 px-4 pe-10 md:pe-4">
                <x-filament-panels::logo class="h-8" />
                <button
                    type="button"
                    aria-label="{{ __('Close sidebar') }}"
                    class="absolute end-3 top-1/2 -translate-y-1/2 rounded-md p-1 text-body-subtle hover:bg-neutral-secondary hover:text-heading md:hidden"
                    @click="sidebarOpen = false"
                >
                    <x-filament::icon icon="heroicon-o-x-mark" class="h-4 w-4" />
                </button>
            </div>

            @if ($layoutMode === 'sidebar')
                @include('velm-filament::partials.nav-sidebar', ['menu' => $menu['menu'] ?? []])
            @else
                <div class="hidden min-h-0 flex-1 flex-col md:contents">
                    @include('velm-filament::partials.nav-app-rail', ['menu' => $menu])
                </div>
                <div class="flex min-h-0 flex-1 flex-col overflow-hidden md:hidden">
                    @include('velm-filament::partials.nav-sidebar', ['menu' => $menu['menu'] ?? []])
                </div>
            @endif

            @include('velm-filament::partials.shell-user-footer')
        </aside>

        <div class="flex min-h-screen w-full min-w-0 flex-1 flex-col">
            <header
                class="velm-shell-header sticky top-0 z-30 flex shrink-0 items-center gap-3 overflow-visible border-b border-default bg-neutral-secondary px-4 md:px-6"
            >
                <button
                    type="button"
                    aria-label="{{ __('Open menu') }}"
                    class="-ms-1.5 rounded-md p-1.5 text-body hover:bg-neutral-secondary md:hidden"
                    @click="sidebarOpen = true"
                >
                    <x-filament::icon icon="heroicon-o-bars-3" class="h-5 w-5" />
                </button>

                @if ($layoutMode === 'apps')
                    <div class="hidden min-w-0 flex-1 overflow-visible md:flex">
                        @include('velm-filament::partials.nav-topbar-secondary', ['menu' => $menu])
                    </div>

                    @if ($activeRoot)
                        <p class="min-w-0 flex-1 truncate px-0.5 text-sm font-semibold text-heading md:hidden">
                            {{ $activeRoot['label'] }}
                        </p>
                    @else
                        <div class="min-w-0 flex-1 md:hidden" aria-hidden="true"></div>
                    @endif
                @else
                    <div class="min-w-0 flex-1" aria-hidden="true"></div>
                @endif

                <div class="flex shrink-0 items-center gap-2">
                    @if (filament()->auth()->check() && filament()->hasUserMenu())
                        @livewire(\Filament\Livewire\SimpleUserMenu::class)
                    @endif
                </div>
            </header>

            <div class="velm-shell-main flex min-h-0 flex-1 flex-col overflow-x-hidden overflow-y-auto">
                <main @class([
                    'fi-main flex-1 px-4 py-5 md:px-6',
                    is_string($maxContentWidth) ? "fi-width-{$maxContentWidth}" : null,
                ])>
                    {{ FilamentView::renderHook(PanelsRenderHook::CONTENT_START, scopes: $renderHookScopes) }}

                    {{ $slot }}

                    {{ FilamentView::renderHook(PanelsRenderHook::CONTENT_END, scopes: $renderHookScopes) }}
                </main>
            </div>
        </div>
    </div>
</x-filament-panels::layout.base>
