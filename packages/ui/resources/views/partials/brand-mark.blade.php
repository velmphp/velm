@php
    $brand = $velmShell ?? [];
    $appName = (string) ($brand['app_name'] ?? config('app.name', 'Velm'));
    $showText = (bool) ($brand['show_header_brand_text'] ?? true);
    $logoLight = (string) ($brand['logo_url_light'] ?? '');
    $logoDark = (string) ($brand['logo_url_dark'] ?? $logoLight);
    $logoStyle = (string) ($brand['header_logo_style'] ?? 'height: 68px; width: auto;');
@endphp

<a
    href="{{ \Velm\Admin\Support\VelmPanel::homeUrl() }}"
    class="flex min-w-0 flex-1 items-center gap-2.5 leading-none text-heading transition hover:opacity-90"
    aria-label="{{ $appName }} home"
>
    @if ($logoLight !== '')
        <img
            src="{{ $logoLight }}"
            alt=""
            class="shrink-0 object-contain"
            style="{{ $logoStyle }}"
            x-show="! isDark"
        />
        @if ($logoDark !== '' && $logoDark !== $logoLight)
            <img
                src="{{ $logoDark }}"
                alt=""
                class="shrink-0 object-contain"
                style="{{ $logoStyle }}"
                x-show="isDark"
                x-cloak
            />
        @endif
    @else
        <span
            class="inline-flex size-10 shrink-0 items-center justify-center rounded-lg bg-fg-brand text-white"
            aria-hidden="true"
        >
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 7.5L12 3l8.25 4.5M3.75 7.5v9L12 21l8.25-4.5v-9M3.75 7.5L12 12m0 0l8.25-4.5M12 12v9"/>
            </svg>
        </span>
    @endif
    @if ($showText)
        <div class="min-w-0 overflow-hidden">
            <span class="block truncate text-sm font-semibold tracking-tight whitespace-nowrap text-heading">
                {{ $appName }}
            </span>
        </div>
    @endif
</a>
