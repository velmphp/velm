@php
    use Velm\Admin\Pages\AppsPage;

    $appsUrl = AppsPage::getUrl();
    $panel = trim((string) config('velm.panel_path', 'velm'), '/');
    $onAppsCatalog = request()->is($panel.'/apps', $panel.'/apps/*');
@endphp

<a
    href="{{ $appsUrl }}"
    wire:navigate
    data-nav-link
    @class([
        'nav-item flex items-center gap-2.5 rounded-md px-3 py-2 text-sm',
        'nav-active' => $onAppsCatalog,
    ])
    @click="sidebarOpen = false"
>
    <x-velm-ui::icon icon="heroicon-o-squares-2x2" class="h-4 w-4 shrink-0" />
    <span class="truncate">{{ __('Apps') }}</span>
</a>
