@php
    use Velm\Admin\Pages\AppsPage;

    $user = \Velm\Admin\Support\VelmPanel::auth()->user();
    $label = $user?->name ?: $user?->email;
    $login = $user?->email;
    $initial = $label !== null && $label !== ''
        ? mb_strtoupper(mb_substr($label, 0, 1))
        : '?';
@endphp

@if ($user && filled($label))
    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
        <button
            type="button"
            @click="open = ! open"
            aria-haspopup="true"
            :aria-expanded="open.toString()"
            class="velm-user-menu-btn"
        >
            <span class="velm-user-avatar" aria-hidden="true">
                {{ $initial }}
            </span>
            <span class="hidden max-w-[120px] truncate text-sm font-medium text-body sm:inline">
                {{ $label }}
            </span>
            <svg
                class="hidden h-3 w-3 text-body-subtle sm:block"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                stroke-width="2"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
            </svg>
        </button>

        <div
            x-show="open"
            x-cloak
            x-transition
            class="pv-dropdown end-0 top-full mt-2 w-56"
        >
            <div class="border-b border-default px-4 py-3">
                <p class="truncate text-sm font-semibold text-heading">{{ $label }}</p>
                @if (filled($login))
                    <p class="text-xs text-body-subtle">{{ $login }}</p>
                @endif
            </div>
            <div class="py-1 text-sm">
                <a href="#" class="block px-4 py-1.5 text-body hover:bg-neutral-secondary hover:text-heading">
                    {{ __('My profile') }}
                </a>
                <a href="{{ AppsPage::getUrl() }}" class="block px-4 py-1.5 text-body hover:bg-neutral-secondary hover:text-heading">
                    {{ __('Apps catalog') }}
                </a>
                <a href="#" class="block px-4 py-1.5 text-body hover:bg-neutral-secondary hover:text-heading">
                    {{ __('Change password') }}
                </a>
                <form method="post" action="{{ \Velm\Admin\Support\VelmPanel::getLogoutUrl() }}">
                    @csrf
                    <button
                        type="submit"
                        class="block w-full px-4 py-1.5 text-start text-fg-danger hover:bg-danger-soft"
                    >
                        {{ __('Sign out') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
@endif
