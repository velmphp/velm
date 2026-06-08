@php
    $livewire ??= null;
    $title = trim(strip_tags((string) ($pageTitle ?? $livewire?->getTitle() ?? '')));
    $usesLivewire = $livewire !== null && $livewire instanceof \Livewire\Component;
    $shell = $velmShell ?? [];
    $appName = (string) ($shell['app_name'] ?? config('app.name', 'Velm'));
    $favicon = (string) ($shell['favicon_url'] ?? '');
    $themeStyle = (string) ($shell['company_theme_style'] ?? '');
    $fontStyle = (string) ($shell['company_font_style'] ?? '');
@endphp

<meta charset="utf-8" />
<meta name="csrf-token" content="{{ csrf_token() }}" />
<meta name="viewport" content="width=device-width, initial-scale=1" />

<meta name="velm-app-name" content="{{ $appName }}" />
<meta name="velm-panel-path" content="{{ trim((string) config('velm.panel_path', 'velm'), '/') }}" />

<title id="velm-document-title">
    @if (filled($title))
        {{ $title }} — {{ $appName }}
    @else
        {{ $appName }}
    @endif
</title>

@if (filled($title))
    <meta name="velm-nav-label" content="{{ $title }}" />
@endif

@if ($favicon !== '')
    <link rel="icon" href="{{ $favicon }}" />
@endif

@if ($themeStyle !== '')
    <style>{!! $themeStyle !!}</style>
@endif

@if ($fontStyle !== '')
    <style>{!! $fontStyle !!}</style>
@endif

@include('velm-admin::partials.head-fonts')

<style>
    [x-cloak] {
        display: none !important;
    }

    /* Sidebar nav — PyVelm main.html */
    .nav-item {
        color: var(--color-body);
    }
    @media (hover: hover) {
        .nav-item:hover {
            background-color: var(--color-brand-softer);
            color: var(--color-fg-brand);
        }
    }
    .nav-item.nav-active {
        background-color: var(--color-brand-soft);
        color: var(--color-fg-brand-strong);
        font-weight: 600;
    }
    :where(.dark, .dark *) .nav-item:hover {
        background-color: var(--color-neutral-tertiary);
        color: var(--color-heading);
    }
    :where(.dark, .dark *) .nav-item.nav-active {
        background-color: var(--color-brand-softer);
        color: var(--color-fg-brand);
    }
</style>

@if ($usesLivewire)
    @livewireStyles
@endif

<link rel="stylesheet" href="{{ \Velm\Ui\UiAssets::stylesheetHref() }}" @if ($usesLivewire) data-navigate-track @endif />

@if (\Velm\Admin\Support\VelmPanel::hasDarkMode() && ! \Velm\Admin\Support\VelmPanel::hasDarkModeForced())
    <script>
        const loadDarkMode = () => {
            window.theme = localStorage.getItem('theme') ?? @js(\Velm\Admin\Support\VelmPanel::getDefaultThemeMode());

            if (
                window.theme === 'dark' ||
                (window.theme === 'system' &&
                    window.matchMedia('(prefers-color-scheme: dark)').matches)
            ) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        };

        loadDarkMode();
        @if ($usesLivewire)
            document.addEventListener('livewire:navigated', loadDarkMode);
        @endif
    </script>
@endif

@stack('styles')
