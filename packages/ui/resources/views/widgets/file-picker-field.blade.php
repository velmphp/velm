@php
    $cfg = [
        'wireKey' => $wireKey,
        'multi' => $multi ?? false,
        'readonly' => $readonly ?? false,
        'accept' => $accept ?? '',
        'initial' => $initial ?? [],
        'pickerTitle' => $pickerTitle ?? ($multi ?? false ? __('Pick files') : __('Pick a file')),
    ];
@endphp

<div
    wire:ignore
    class="pv-file-picker-field"
    x-data="pvFilePickerField(@js($cfg))"
>
    <div class="flex flex-wrap items-center gap-2">
        <template x-for="row in selected" :key="row.id">
            <div class="inline-flex items-center gap-2 rounded-md border border-default bg-neutral-secondary px-2 py-1 text-xs">
                <template x-if="row.thumbnail_url">
                    <img
                        :src="row.thumbnail_url"
                        alt=""
                        class="rounded object-cover"
                        loading="lazy"
                        x-on:error="$el.style.display = 'none'"
                    />
                </template>
                <template x-if="!row.thumbnail_url">
                    <span
                        class="inline-flex h-8 w-8 items-center justify-center rounded bg-neutral-primary text-2xs uppercase text-body-subtle"
                        x-text="(row.mimetype || '').split('/').pop() || 'file'"
                    ></span>
                </template>
                <div class="max-w-[12rem] min-w-0">
                    <a
                        :href="row.download_url || ('/api/attachment/' + row.id + '/download')"
                        target="_blank"
                        class="block truncate text-body hover:text-fg-brand hover:underline"
                        x-text="row.name"
                    ></a>
                </div>
                <button
                    type="button"
                    x-show="!readonly"
                    @click="remove(row.id)"
                    class="text-body-subtle hover:text-fg-danger"
                    aria-label="{{ __('Remove') }}"
                >
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </template>

        <template x-if="!readonly && (multi || !selected.length)">
            <button
                type="button"
                @click="openPicker()"
                class="inline-flex items-center gap-1.5 rounded-md border border-fg-brand/30 px-2.5 py-1.5 text-xs font-medium text-fg-brand transition hover:bg-brand-softer"
            >
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 7.5m0 0L7.5 12M12 7.5v9" />
                </svg>
                <span x-text="multi && selected.length ? '{{ __('Add another') }}' : pickerTitle"></span>
            </button>
        </template>

        <template x-if="readonly && !selected.length">
            <span class="text-xs text-body-subtle">{{ __('No file.') }}</span>
        </template>
    </div>
</div>
