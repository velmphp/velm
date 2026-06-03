@php
    $user = \Velm\Admin\Support\VelmPanel::auth()->user();
    $label = filled(data_get($user, 'name')) ? data_get($user, 'name') : data_get($user, 'email');
@endphp

@if ($user && filled($label))
    <div class="shrink-0 px-4 py-3 text-xs text-body-subtle">
        <p class="truncate">
            {{ __('Signed in as') }}
            <span class="font-semibold text-heading">{{ $label }}</span>
        </p>
    </div>
@endif
