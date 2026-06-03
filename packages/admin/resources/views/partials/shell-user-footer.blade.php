@php
    $user = \Velm\Admin\Support\VelmPanel::auth()->user();
@endphp

@if ($user)
    <div class="shrink-0 space-y-2 border-t border-default/60 px-4 py-3 text-xs text-body-subtle">
        <p class="truncate">
            {{ __('Signed in as') }}
            <span class="font-semibold text-heading">{{ $user->name ?? $user->email }}</span>
        </p>
        <form method="post" action="{{ \Velm\Admin\Support\VelmPanel::getLogoutUrl() }}">
            @csrf
            <button
                type="submit"
                class="text-fg-brand transition hover:underline"
            >
                {{ __('Sign out') }}
            </button>
        </form>
    </div>
@endif
