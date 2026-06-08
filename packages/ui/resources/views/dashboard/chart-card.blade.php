@php
    $title = (string) ($widget['title'] ?? '');
    $icon = (string) ($widget['icon'] ?? 'heroicon-o-chart-bar');
    $data = is_array($widget['data'] ?? null) ? $widget['data'] : [];
    $labels = is_array($data['labels'] ?? null) ? $data['labels'] : [];
    $values = is_array($data['values'] ?? null) ? $data['values'] : [];
    $measureLabel = (string) ($data['measure_label'] ?? '');
    $chartType = (string) ($data['chart_type'] ?? 'bar');
    $href = isset($data['href']) && is_string($data['href']) ? $data['href'] : null;
    $chartConfig = [
        'labels' => $labels,
        'values' => $values,
        'measureLabel' => $measureLabel,
        'chartType' => $chartType,
        'height' => 240,
    ];
@endphp

<article
    class="flex h-full flex-col rounded-lg border border-default bg-neutral-primary shadow-xs"
    x-data="pvDashboardChart(@js($chartConfig))"
>
    <div class="flex items-center justify-between gap-3 border-b border-default/60 px-4 py-3">
        <div class="flex min-w-0 items-center gap-2">
            <x-velm-ui::icon :icon="$icon" class="h-4 w-4 shrink-0 text-fg-brand" />
            <h2 class="truncate text-sm font-semibold text-heading">{{ $title }}</h2>
        </div>
        @if ($href)
            <a href="{{ $href }}" wire:navigate class="shrink-0 text-xs font-medium text-fg-brand hover:underline">
                {{ $data['action_label'] ?? __('Open chart') }}
            </a>
        @endif
    </div>

    <div class="px-4 py-4">
        <template x-if="!hasData">
            <p class="py-4 text-sm text-body-subtle">{{ __('No data to display') }}</p>
        </template>
        <div
            x-show="hasData"
            x-ref="mount"
            wire:ignore
            class="w-full min-h-[240px]"
        ></div>
    </div>
</article>

@once
    @push('scripts')
        <script defer src="{{ \Velm\Ui\UiAssets::graphScriptHref() }}" data-navigate-track></script>
    @endpush
@endonce
