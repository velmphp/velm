@php
    use Velm\Admin\Pages\ProfilePage;
@endphp

<div
    class="mx-auto max-w-lg space-y-4"
    data-velm-breadcrumb-trail="{{ $this->velmBreadcrumbTrailJson() }}"
    data-velm-nav-label="{{ $this->velmNavLabel() }}"
>
    @include('velm-ui::partials.breadcrumbs')

    <div>
        <h1 class="text-lg font-semibold text-heading">{{ $this->getTitle() }}</h1>
        <p class="mt-1 text-sm text-body-subtle">
            {{ __('Choose a strong password with at least 8 characters.') }}
        </p>
    </div>

    <div class="rounded-xl border border-default bg-neutral-primary px-6 py-6 shadow-sm">
        <form wire:submit="savePassword" class="space-y-4">
            <div>
                <label for="password-current" class="mb-1.5 block text-xs font-medium text-body">
                    {{ __('Current password') }}
                </label>
                <input
                    id="password-current"
                    type="password"
                    wire:model="data.current_password"
                    required
                    autocomplete="current-password"
                    class="pv-input w-full"
                />
                @error('data.current_password')
                    <p class="mt-1 text-xs text-fg-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password-new" class="mb-1.5 block text-xs font-medium text-body">
                    {{ __('New password') }}
                </label>
                <input
                    id="password-new"
                    type="password"
                    wire:model="data.password"
                    required
                    autocomplete="new-password"
                    class="pv-input w-full"
                />
                @error('data.password')
                    <p class="mt-1 text-xs text-fg-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password-confirm" class="mb-1.5 block text-xs font-medium text-body">
                    {{ __('Confirm new password') }}
                </label>
                <input
                    id="password-confirm"
                    type="password"
                    wire:model="data.password_confirmation"
                    required
                    autocomplete="new-password"
                    class="pv-input w-full"
                />
                @error('data.password_confirmation')
                    <p class="mt-1 text-xs text-fg-danger">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-wrap items-center gap-3 pt-2">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="savePassword"
                    class="rounded-md bg-fg-brand px-4 py-2 text-sm font-medium text-white transition-opacity hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="savePassword">{{ __('Update password') }}</span>
                    <span wire:loading wire:target="savePassword">{{ __('Updating…') }}</span>
                </button>
                <a
                    href="{{ ProfilePage::getUrl() }}"
                    wire:navigate
                    class="text-sm text-fg-brand hover:underline"
                >
                    {{ __('Back to profile') }}
                </a>
            </div>
        </form>
    </div>
</div>
