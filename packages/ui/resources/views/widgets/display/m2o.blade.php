@php
    $label = '—';
    if (! empty($initialLabel)) {
        $label = $initialLabel;
    } elseif (($initialId ?? null) !== null) {
        $label = (string) $initialId;
    }
    $recordUrl = ($formViewUrl ?? null) && ($initialId ?? null)
        ? \Velm\Ui\Support\ViewUrlResolver::recordHref($formViewUrl, (int) $initialId)
        : null;
@endphp

@if ($recordUrl)
    <button
        type="button"
        onclick="pvOpenRecord(@js($recordUrl), @js($label))"
        class="text-start text-sm text-fg-brand hover:underline"
    >
        {{ $label }}
    </button>
@else
    <p class="text-sm text-body">{{ $label }}</p>
@endif
