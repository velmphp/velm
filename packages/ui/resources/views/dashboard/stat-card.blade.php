@php
    $title = (string) ($widget['title'] ?? '');
    $icon = (string) ($widget['icon'] ?? 'heroicon-o-chart-bar');
    $data = is_array($widget['data'] ?? null) ? $widget['data'] : [];
    $value = $data['value'] ?? $data['count'] ?? '—';
    $label = (string) ($data['label'] ?? '');
    $href = isset($data['href']) && is_string($data['href']) ? $data['href'] : null;
@endphp

<article class="flex h-full flex-col rounded-lg border border-default bg-neutral-primary p-4 shadow-xs">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <h2 class="text-sm font-medium text-body-subtle">{{ $title }}</h2>
            <p class="mt-2 text-3xl font-semibold tracking-tight text-heading">{{ $value }}</p>
            @if ($label !== '')
                <p class="mt-1 text-sm text-body-subtle">{{ $label }}</p>
            @endif
        </div>
        <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-lg bg-neutral-secondary text-fg-brand">
            <x-velm-ui::icon :icon="$icon" class="h-5 w-5" />
        </span>
    </div>

    @if ($href)
        <div class="mt-4 border-t border-default/60 pt-3">
            <a href="{{ $href }}" wire:navigate class="text-sm font-medium text-fg-brand hover:underline">
                {{ $data['action_label'] ?? __('View all') }}
            </a>
        </div>
    @endif
</article>
