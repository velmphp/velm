@php
    $title = (string) ($widget['title'] ?? '');
    $icon = (string) ($widget['icon'] ?? 'heroicon-o-chart-bar');
    $data = is_array($widget['data'] ?? null) ? $widget['data'] : [];
    $points = is_array($data['points'] ?? null) ? $data['points'] : [];
    $measureLabel = (string) ($data['measure_label'] ?? '');
    $href = isset($data['href']) && is_string($data['href']) ? $data['href'] : null;
    $max = max(1.0, ...array_map(static fn (array $point): float => (float) ($point['value'] ?? 0), $points ?: [['value' => 1]]));
@endphp

<article class="flex h-full flex-col rounded-lg border border-default bg-neutral-primary shadow-xs">
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

    @if ($points === [])
        <p class="px-4 py-6 text-sm text-body-subtle">{{ __('No data to display') }}</p>
    @else
        <div class="space-y-3 px-4 py-4">
            @if ($measureLabel !== '')
                <p class="text-xs text-body-subtle">{{ $measureLabel }}</p>
            @endif
            @foreach ($points as $index => $point)
                @php
                    $point = is_array($point) ? $point : [];
                    $label = (string) ($point['label'] ?? '—');
                    $value = (float) ($point['value'] ?? 0);
                    $width = min(100, max(4, (int) round(($value / $max) * 100)));
                @endphp
                <div wire:key="chart-point-{{ $widget['id'] ?? 'widget' }}-{{ $index }}">
                    <div class="mb-1 flex items-center justify-between gap-2 text-xs">
                        <span class="truncate text-body">{{ $label }}</span>
                        <span class="shrink-0 font-medium text-heading">{{ $value == (int) $value ? (int) $value : number_format($value, 1) }}</span>
                    </div>
                    <div class="h-2 rounded-full bg-neutral-secondary">
                        <div class="h-2 rounded-full bg-fg-brand" style="width: {{ $width }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</article>
