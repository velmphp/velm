@php
    $on = filter_var($value ?? false, FILTER_VALIDATE_BOOLEAN);
@endphp

@if ($on)
    <span class="inline-flex items-center rounded-full bg-success-soft px-2 py-0.5 text-xs font-semibold text-fg-success-strong">{{ __('Yes') }}</span>
@else
    <span class="inline-flex items-center rounded-full bg-neutral-tertiary px-2 py-0.5 text-xs font-semibold text-body-subtle">{{ __('No') }}</span>
@endif
