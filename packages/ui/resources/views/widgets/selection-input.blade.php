@php
    $choices = $choices ?? [];
    $current = (string) ($value ?? '');
@endphp

<select
    wire:model="data.{{ $name }}"
    @disabled($readonly ?? false)
    @required($required ?? false)
    class="pv-input"
>
    @foreach ($choices as $choice)
        @php
            $optionValue = (string) ($choice['value'] ?? '');
            $optionLabel = (string) ($choice['label'] ?? $optionValue);
        @endphp
        <option value="{{ $optionValue }}" @selected($current === $optionValue)>{{ $optionLabel }}</option>
    @endforeach
</select>
