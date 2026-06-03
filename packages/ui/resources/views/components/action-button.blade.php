@props([
    'icon',
    'label',
    'type' => 'button',
    'form' => null,
])

@php
    $heroicon = str_contains((string) $icon, 'heroicon') ? $icon : "heroicon-o-{$icon}";
@endphp

<button
    type="{{ $type }}"
    @if ($form) form="{{ $form }}" @endif
    {{ $attributes->class(['pv-btn inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition']) }}
>
    <x-velm-ui::icon :icon="$heroicon" class="h-4 w-4 shrink-0" />
    <span>{{ $label }}</span>
</button>
