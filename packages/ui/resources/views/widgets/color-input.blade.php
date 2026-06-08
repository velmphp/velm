@php
    $current = is_string($value ?? null) ? trim((string) $value) : '';
    $picker = $current !== '' && preg_match('/^#[0-9a-fA-F]{6}$/', $current) ? $current : '#6366f1';
@endphp

<div class="flex items-center gap-2">
    <input
        type="color"
        value="{{ $picker }}"
        @disabled($readonly ?? false)
        class="h-10 w-12 cursor-pointer rounded border border-default bg-surface p-1"
        x-data
        x-on:input="$wire.set('data.{{ $name }}', $event.target.value)"
    />
    <input
        type="text"
        wire:model="data.{{ $name }}"
        @readonly($readonly ?? false)
        @required($required ?? false)
        placeholder="#6366f1"
        class="pv-input flex-1"
    />
</div>
