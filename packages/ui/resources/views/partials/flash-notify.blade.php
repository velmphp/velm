@php
    $notify = session('velm_notify');
@endphp

@if (is_array($notify) && filled($notify['title'] ?? null))
    <div
        x-data="{ show: true }"
        x-show="show"
        x-init="setTimeout(() => show = false, 4000)"
        x-transition
        class="fixed end-4 bottom-4 z-50 max-w-sm rounded-lg border border-default bg-neutral-primary px-4 py-3 shadow-lg"
        role="status"
    >
        <p @class([
            'text-sm font-semibold',
            'text-success-strong' => ($notify['type'] ?? '') === 'success',
            'text-fg-danger' => ($notify['type'] ?? '') === 'danger',
            'text-heading' => ! in_array($notify['type'] ?? '', ['success', 'danger'], true),
        ])>
            {{ $notify['title'] }}
        </p>
        @if (filled($notify['body'] ?? null))
            <p class="mt-0.5 text-xs text-body-subtle">{{ $notify['body'] }}</p>
        @endif
        <button type="button" @click="show = false" class="absolute end-2 top-2 text-body-subtle hover:text-heading" aria-label="{{ __('Dismiss') }}">×</button>
    </div>
@endif
