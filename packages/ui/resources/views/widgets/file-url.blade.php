@php
    $cfg = [
        'wireKey' => $wireKey,
        'fallbackWireKey' => $fallbackWireKey ?? null,
        'accept' => $accept ?? 'image/*',
        'readonly' => $readonly ?? false,
        'initial' => (string) ($value ?? ''),
        'pickerTitle' => $label ?? $name ?? __('Choose file'),
    ];
@endphp

<div
    wire:ignore
    class="pv-file-url"
    x-data="pvFileUrl(@js($cfg))"
>
    <div class="flex flex-col gap-2 sm:flex-row sm:items-start">
        <div
            x-show="previewUrl"
            x-cloak
            class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-default bg-neutral-secondary"
        >
            <img :src="previewUrl" alt="" class="max-h-full max-w-full object-contain" loading="lazy" />
        </div>

        <div class="min-w-0 flex-1 space-y-2">
            <div class="flex flex-wrap items-stretch gap-2">
                <input
                    type="text"
                    inputmode="url"
                    autocomplete="off"
                    x-model="value"
                    :readonly="readonly"
                    @required($required ?? false)
                    placeholder="{{ __('URL or pick from library…') }}"
                    class="pv-input min-w-0 flex-1"
                />
                <button
                    type="button"
                    @click="pick()"
                    :disabled="readonly"
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-md border border-default px-3 py-1.5 text-sm font-medium text-body transition hover:bg-neutral-secondary disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <svg class="h-4 w-4 text-fg-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-7.5 3v3.75M9 21h6" />
                    </svg>
                    {{ __('Browse…') }}
                </button>
                <button
                    type="button"
                    x-show="value && !readonly"
                    @click="clear()"
                    class="inline-flex shrink-0 items-center rounded-md border border-default px-3 py-1.5 text-sm text-body-subtle transition hover:bg-danger-soft hover:text-fg-danger"
                >
                    {{ __('Clear') }}
                </button>
            </div>
            @unless ($readonly ?? false)
                <p class="text-2xs text-body-subtle">
                    @if (! empty($fallbackWireKey))
                        {{ __('Optional. When empty, the light logo is used in dark mode.') }}
                    @else
                        {{ __('Opens the file library. Images are marked public for use in branding.') }}
                    @endif
                </p>
            @endunless
        </div>
    </div>
</div>
