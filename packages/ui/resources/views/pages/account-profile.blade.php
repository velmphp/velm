@php
    use Velm\Admin\Pages\ChangePasswordPage;

    $companyOptions = $this->companyOptions();
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
            {{ __('Update your display name, sign-in email, and default company.') }}
        </p>
    </div>

    <div class="rounded-xl border border-default bg-neutral-primary px-6 py-6 shadow-sm">
        <form wire:submit="saveProfile" class="space-y-4">
            <div>
                <label for="profile-name" class="mb-1.5 block text-xs font-medium text-body">
                    {{ __('Name') }}
                </label>
                <input
                    id="profile-name"
                    type="text"
                    wire:model="data.name"
                    required
                    autocomplete="name"
                    class="pv-input w-full"
                />
                @error('data.name')
                    <p class="mt-1 text-xs text-fg-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="profile-email" class="mb-1.5 block text-xs font-medium text-body">
                    {{ __('Email') }}
                </label>
                <input
                    id="profile-email"
                    type="email"
                    wire:model="data.email"
                    required
                    autocomplete="username"
                    class="pv-input w-full"
                />
                @error('data.email')
                    <p class="mt-1 text-xs text-fg-danger">{{ $message }}</p>
                @enderror
            </div>

            @if ($companyOptions !== [])
                <div>
                    <label for="profile-company" class="mb-1.5 block text-xs font-medium text-body">
                        {{ __('Default company') }}
                    </label>
                    <select
                        id="profile-company"
                        wire:model="data.company_id"
                        class="pv-input w-full"
                    >
                        <option value="">{{ __('— None —') }}</option>
                        @foreach ($companyOptions as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-body-subtle">
                        {{ __('Used when no company is selected in the shell. Session company switching is separate.') }}
                    </p>
                    @error('data.company_id')
                        <p class="mt-1 text-xs text-fg-danger">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            <div class="flex flex-wrap items-center gap-3 pt-2">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="saveProfile"
                    class="rounded-md bg-fg-brand px-4 py-2 text-sm font-medium text-white transition-opacity hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="saveProfile">{{ __('Save') }}</span>
                    <span wire:loading wire:target="saveProfile">{{ __('Saving…') }}</span>
                </button>
                <a
                    href="{{ ChangePasswordPage::getUrl() }}"
                    wire:navigate
                    class="text-sm text-fg-brand hover:underline"
                >
                    {{ __('Change password') }}
                </a>
            </div>
        </form>
    </div>
</div>
