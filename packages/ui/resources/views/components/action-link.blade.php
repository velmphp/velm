@props([
    'href',
    'icon',
    'label',
    'navigate' => true,
    'variant' => 'icon',
])

@php
    $heroicon = str_contains((string) $icon, 'heroicon') ? $icon : "heroicon-o-{$icon}";
@endphp

<a
    href="{{ $href }}"
    title="{{ $label }}"
    @if ($navigate) wire:navigate @endif
    {{ $attributes->class([
        'pv-row-action inline-flex shrink-0 items-center justify-center rounded-md p-1.5 text-fg-brand transition-colors hover:bg-brand-soft',
        'gap-1.5 px-2 py-1.5 text-sm font-medium' => $variant === 'labeled',
    ]) }}
>
    <x-velm-ui::icon :icon="$heroicon" class="h-4 w-4 shrink-0" />
    @if ($variant === 'labeled')
        <span>{{ $label }}</span>
    @else
        <span class="sr-only">{{ $label }}</span>
    @endif
</a>
