@props([
    'livewire' => null,
])

@php
    $embed = request()->boolean('embed');
    $menu = $velmMenu ?? ['menu' => [], 'menu_layout' => 'apps'];
    $layoutMode = $menu['menu_layout'] ?? 'apps';
    $activeRoot = $menu['menu_active_root'] ?? null;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('velm-ui::partials.document-head', ['livewire' => $livewire])
    </head>
    @if ($embed)
        <body class="min-h-screen bg-neutral-primary font-sans text-body antialiased">
            <main class="px-4 py-4 md:px-5">
                {{ $slot }}
            </main>

            @include('velm-ui::partials.document-foot', ['livewire' => $livewire])
        </body>
    @else
        <body
            class="flex min-h-screen bg-neutral-secondary font-sans text-body antialiased md:flex-row"
            x-data="{
                sidebarOpen: false,
                isDark: false,
                init() {
                    this.syncThemeState();
                    document.addEventListener('livewire:navigated', () => this.syncThemeState());
                },
                syncThemeState() {
                    this.isDark = document.documentElement.classList.contains('dark');
                },
                toggleTheme() {
                    const html = document.documentElement;
                    const nextDark = ! html.classList.contains('dark');
                    if (nextDark) {
                        html.classList.add('dark');
                        localStorage.setItem('theme', 'dark');
                    } else {
                        html.classList.remove('dark');
                        localStorage.setItem('theme', 'light');
                    }
                    this.isDark = nextDark;
                },
            }"
            @keydown.escape.window="sidebarOpen = false"
        >
            <div
                x-cloak
                x-show="sidebarOpen"
                x-transition.opacity
                class="fixed inset-0 z-20 bg-black/30 md:hidden"
                @click="sidebarOpen = false"
            ></div>

            <aside
                class="fixed inset-y-0 left-0 z-30 flex w-64 min-h-screen flex-shrink-0 flex-col bg-neutral-secondary transition-transform duration-200 ease-in-out md:relative md:translate-x-0"
                :class="{ '-translate-x-full': ! sidebarOpen }"
            >
                <div
                    class="relative flex shrink-0 items-center overflow-hidden border-b border-default/60 px-4 pr-10 md:pr-4"
                    style="height: {{ (int) ($velmShell['header_logo_height'] ?? 68) }}px"
                >
                    @include('velm-ui::partials.brand-mark')
                    <button
                        type="button"
                        aria-label="{{ __('Close sidebar') }}"
                        class="absolute right-3 top-1/2 -translate-y-1/2 rounded-md p-1 text-body-subtle hover:bg-neutral-secondary hover:text-heading md:hidden"
                        @click="sidebarOpen = false"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                @if ($layoutMode === 'apps_catalog')
                    @include('velm-admin::partials.nav-apps-sidebar', ['menu' => $menu])
                @elseif ($layoutMode === 'sidebar')
                    @include('velm-admin::partials.nav-sidebar', ['menu' => $menu['menu'] ?? []])
                @else
                    <div class="hidden min-h-0 flex-1 flex-col md:contents">
                        @include('velm-admin::partials.nav-app-rail', ['menu' => $menu])
                    </div>
                    <div class="flex min-h-0 flex-1 flex-col overflow-hidden md:hidden">
                        @include('velm-admin::partials.nav-sidebar', ['menu' => $menu['menu'] ?? []])
                    </div>
                @endif

                @include('velm-admin::partials.shell-user-sidebar')
            </aside>

            <div class="flex min-h-screen w-full min-w-0 flex-1 flex-col">
                <header
                    class="sticky top-0 z-30 flex min-h-[60px] shrink-0 items-center gap-3 overflow-visible border-b border-default bg-neutral-secondary px-4 md:px-6"
                >
                    <button
                        type="button"
                        aria-label="{{ __('Open menu') }}"
                        class="-ml-1.5 rounded-md p-1.5 text-body hover:bg-neutral-secondary md:hidden"
                        @click="sidebarOpen = true"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    @if ($layoutMode === 'apps_catalog')
                        <p class="min-w-0 flex-1 truncate px-0.5 text-sm font-semibold text-heading">
                            {{ __('Apps') }}
                        </p>
                    @elseif ($layoutMode === 'apps')
                        <div class="hidden min-w-0 flex-1 overflow-visible md:flex">
                            @include('velm-admin::partials.nav-topbar-secondary', ['menu' => $menu])
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

                    @include('velm-admin::partials.shell-topbar-actions')
                </header>

                <div class="flex min-h-0 w-full flex-1 flex-col overflow-x-hidden">
                    <main class="flex-1 overflow-auto px-4 py-5 md:px-6">
                        {{ $slot }}
                    </main>
                </div>
            </div>

            @include('velm-ui::partials.record-dialog')
            @include('velm-ui::partials.document-foot', ['livewire' => $livewire])
            @if ($layoutMode === 'apps_catalog')
                @include('velm-admin::partials.apps-catalog-store')
            @endif
            @include('velm-ui::partials.record-dialog-scripts')
        </body>
    @endif
</html>
