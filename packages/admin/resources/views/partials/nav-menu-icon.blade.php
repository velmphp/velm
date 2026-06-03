@php
    $icon = $icon ?? null;
@endphp

@if (filled($icon))
    @php
        $heroicon = str_contains((string) $icon, 'heroicon') ? $icon : "heroicon-o-{$icon}";
    @endphp
    <x-velm-ui::icon :icon="$heroicon" class="h-4 w-4 shrink-0" />
@endif
