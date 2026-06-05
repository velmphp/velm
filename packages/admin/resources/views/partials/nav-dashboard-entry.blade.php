@php
    use Velm\Admin\Pages\DashboardPage;

    $dashboardUrl = DashboardPage::getUrl();
    $panel = trim((string) config('velm.panel_path', 'velm'), '/');
    $onDashboard = request()->is($panel.'/dashboard', $panel.'/dashboard/*');
@endphp

<a
    href="{{ $dashboardUrl }}"
    wire:navigate
    data-nav-link
    @class([
        'nav-item flex items-center gap-2.5 rounded-md px-3 py-2 text-sm',
        'nav-active' => $onDashboard,
    ])
    @click="sidebarOpen = false"
>
    @include('velm-admin::partials.nav-menu-icon', ['icon' => 'home'])
    <span class="truncate">{{ __('Dashboard') }}</span>
</a>
