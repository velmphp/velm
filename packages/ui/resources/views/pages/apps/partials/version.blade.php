@props([
    'app',
    'compact' => false,
])

@php
    $state = (string) ($app['state'] ?? '');
    $installed = $app['installed_version'] ?? null;
    $available = (string) ($app['available_version'] ?? '');
@endphp

@if ($compact)
    <span @class([
        'shrink-0 font-mono text-2xs',
        'text-warning-strong' => $state === 'to_upgrade',
        'text-body-subtle' => $state !== 'to_upgrade',
    ])>
        @if ($state === 'to_upgrade')
            {{ $installed }} → {{ $available }}
        @elseif ($installed)
            {{ $installed }}
        @else
            {{ $available }}
        @endif
    </span>
@else
    <span @class([
        'text-body',
        'text-warning-strong' => $state === 'to_upgrade',
    ])>
        @if ($state === 'to_upgrade')
            {{ $installed }} → {{ $available }}
        @elseif ($state === 'needs_sync')
            {{ $installed }}
            <span class="text-warning-strong">({{ __('sync pending') }})</span>
        @elseif ($installed)
            {{ $installed }}
            @if ($available !== '' && $available !== $installed)
                <span class="text-body-subtle">({{ __('available') }} {{ $available }})</span>
            @endif
        @else
            {{ $available }}
        @endif
    </span>
@endif
