@php
    $user = filament()->auth()->user();
@endphp

@if ($user)
    <div class="shrink-0 border-t border-default/60 px-4 py-3 text-xs text-body-subtle">
        <p class="truncate">
            {{ __('Signed in as') }}
            <span class="font-semibold text-heading">{{ $user->name ?? $user->email }}</span>
        </p>
    </div>
@endif
