@php
    $livewire ??= null;
    $title = trim(strip_tags($livewire?->getTitle() ?? ''));
    $appName = (string) (($velmShell ?? [])['app_name'] ?? config('app.name', 'Velm'));
@endphp

<meta charset="utf-8" />
<meta name="csrf-token" content="{{ csrf_token() }}" />
<meta name="viewport" content="width=device-width, initial-scale=1" />

<title>
    @if (filled($title))
        {{ $title }} — {{ $appName }}
    @else
        {{ $appName }}
    @endif
</title>

@include('velm-admin::partials.head-fonts')

<link rel="stylesheet" href="{{ \Velm\Ui\UiAssets::stylesheetHref() }}" />

<style>
    [x-cloak] {
        display: none !important;
    }
</style>

@livewireStyles

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
    </script>
@endif
