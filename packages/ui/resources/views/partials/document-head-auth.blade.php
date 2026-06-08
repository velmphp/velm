@php
    $livewire ??= null;
    $title = trim(strip_tags($livewire?->getTitle() ?? ''));
    $shell = $velmShell ?? [];
    $appName = (string) ($shell['app_name'] ?? config('app.name', 'Velm'));
    $themeStyle = (string) ($shell['company_theme_style'] ?? '');
    $fontStyle = (string) ($shell['company_font_style'] ?? '');
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

@if ($themeStyle !== '')
    <style>{!! $themeStyle !!}</style>
@endif

@if ($fontStyle !== '')
    <style>{!! $fontStyle !!}</style>
@endif

@include('velm-admin::partials.head-fonts')

@include('velm-ui::partials.velm-theme-scripts')

<link rel="stylesheet" href="{{ \Velm\Ui\UiAssets::stylesheetHref() }}" />

<style>
    [x-cloak] {
        display: none !important;
    }
</style>

@livewireStyles
