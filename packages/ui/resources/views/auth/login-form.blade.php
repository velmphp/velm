<div class="rounded-xl border border-default bg-neutral-primary px-8 py-10 shadow-sm">
    @include('velm-ui::auth.login-header')

    @if ($errors->any())
        <div
            class="mb-4 rounded-md border border-fg-danger/30 bg-danger-soft px-3 py-2 text-sm text-fg-danger"
            role="alert"
        >
            @foreach ($errors->all() as $message)
                <p>{{ $message }}</p>
            @endforeach
        </div>
    @endif

    <form wire:submit="authenticate" class="space-y-4">
        <div>
            <label for="login-email" class="mb-1.5 block text-xs font-medium text-body">
                {{ __('Email') }}
            </label>
            <input
                id="login-email"
                type="email"
                wire:model="data.email"
                required
                autofocus
                autocomplete="username"
                class="pv-input"
            />
            @error('data.email')
                <p class="mt-1 text-xs text-fg-danger">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="login-password" class="mb-1.5 block text-xs font-medium text-body">
                {{ __('Password') }}
            </label>
            <input
                id="login-password"
                type="password"
                wire:model="data.password"
                required
                autocomplete="current-password"
                class="pv-input"
            />
            @error('data.password')
                <p class="mt-1 text-xs text-fg-danger">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex cursor-pointer items-center gap-2 text-sm text-body">
            <input
                type="checkbox"
                wire:model="data.remember"
                class="h-4 w-4 rounded border-default text-fg-brand focus:ring-fg-brand/40"
            />
            {{ __('Remember me') }}
        </label>

        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="authenticate"
            class="w-full rounded-md bg-fg-brand px-4 py-2 text-sm font-medium text-white transition-opacity hover:opacity-90
                   disabled:cursor-not-allowed disabled:opacity-60"
        >
            <span wire:loading.remove wire:target="authenticate">{{ __('Sign in') }}</span>
            <span wire:loading wire:target="authenticate">{{ __('Signing in…') }}</span>
        </button>
    </form>
</div>
