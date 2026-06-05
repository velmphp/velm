@php
    $title = (string) ($widget['title'] ?? '');
    $icon = (string) ($widget['icon'] ?? 'heroicon-o-queue-list');
    $data = is_array($widget['data'] ?? null) ? $widget['data'] : [];
    $items = is_array($data['items'] ?? null) ? $data['items'] : [];
    $emptyLabel = (string) ($data['empty_label'] ?? __('Nothing pending'));
    $href = isset($data['href']) && is_string($data['href']) ? $data['href'] : null;
@endphp

<article class="flex h-full flex-col rounded-lg border border-default bg-neutral-primary shadow-xs">
    <div class="flex items-center justify-between gap-3 border-b border-default/60 px-4 py-3">
        <div class="flex min-w-0 items-center gap-2">
            <x-velm-ui::icon :icon="$icon" class="h-4 w-4 shrink-0 text-fg-brand" />
            <h2 class="truncate text-sm font-semibold text-heading">{{ $title }}</h2>
        </div>
        @if ($href)
            <a href="{{ $href }}" wire:navigate class="shrink-0 text-xs font-medium text-fg-brand hover:underline">
                {{ $data['action_label'] ?? __('View all') }}
            </a>
        @endif
    </div>

    @if ($items === [])
        <p class="px-4 py-6 text-sm text-body-subtle">{{ $emptyLabel }}</p>
    @else
        <ul class="divide-y divide-default/60">
            @foreach ($items as $item)
                @php
                    $item = is_array($item) ? $item : [];
                    $label = (string) ($item['label'] ?? $item['record_label'] ?? '');
                    $meta = (string) ($item['meta'] ?? $item['state_label'] ?? '');
                    $sublabel = (string) ($item['sublabel'] ?? $item['transition_label'] ?? '');
                    $itemHref = isset($item['href']) && is_string($item['href'])
                        ? $item['href']
                        : (isset($item['form_href']) && is_string($item['form_href']) ? $item['form_href'] : null);
                @endphp
                <li>
                    @if ($itemHref)
                        <a
                            href="{{ $itemHref }}"
                            wire:navigate
                            class="block px-4 py-3 transition hover:bg-neutral-secondary"
                        >
                            <span class="block truncate text-sm font-medium text-heading">{{ $label }}</span>
                            @if ($sublabel !== '')
                                <span class="mt-0.5 block truncate text-xs text-body-subtle">{{ $sublabel }}</span>
                            @endif
                            @if ($meta !== '')
                                <span class="mt-1 inline-flex rounded-md bg-neutral-secondary px-2 py-0.5 text-xs text-body-subtle">{{ $meta }}</span>
                            @endif
                        </a>
                    @else
                        <div class="px-4 py-3">
                            <span class="block truncate text-sm font-medium text-heading">{{ $label }}</span>
                            @if ($sublabel !== '')
                                <span class="mt-0.5 block truncate text-xs text-body-subtle">{{ $sublabel }}</span>
                            @endif
                            @if ($meta !== '')
                                <span class="mt-1 inline-flex rounded-md bg-neutral-secondary px-2 py-0.5 text-xs text-body-subtle">{{ $meta }}</span>
                            @endif
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</article>
