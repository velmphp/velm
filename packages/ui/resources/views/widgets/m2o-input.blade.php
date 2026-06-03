@php
    $cfg = [
        'wireKey' => $wireKey,
        'comodel' => $comodel,
        'searchUrl' => $searchUrl,
        'formViewUrl' => $formViewUrl,
        'initialId' => $initialId,
        'initialLabel' => $initialLabel,
        'readonly' => $readonly ?? false,
        'canQuickCreate' => $canQuickCreate ?? false,
        'createUrl' => $createUrl ?? null,
    ];
@endphp

<div
    wire:ignore
    class="pv-m2o relative"
    x-data="pvM2o(@js($cfg))"
    @click.outside="close()"
    @keydown.escape.prevent.stop="close()"
>
    <div class="flex items-stretch gap-1">
        <div class="relative flex-1">
            <input
                type="text"
                x-ref="input"
                x-model="query"
                :readonly="readonly"
                @click="readonly && value !== null && formViewUrl && openRecord()"
                @focus="onFocus()"
                @input.debounce.250ms="onInput()"
                @keydown.arrow-down.prevent="moveCursor(1)"
                @keydown.arrow-up.prevent="moveCursor(-1)"
                @keydown.enter.prevent="onEnter()"
                placeholder="{{ __('Search…') }}"
                autocomplete="off"
                class="pv-m2o-input w-full rounded border border-default bg-neutral-primary px-2 py-1 text-sm text-heading placeholder:text-body-subtle focus:border-fg-brand focus:outline-none focus:ring-2 focus:ring-fg-brand disabled:cursor-not-allowed disabled:opacity-60"
            />

            <button
                type="button"
                x-show="value !== null && !readonly"
                @click.stop="clearSelection()"
                aria-label="{{ __('Clear') }}"
                class="absolute right-1.5 top-1/2 -translate-y-1/2 rounded p-0.5 text-body-subtle hover:bg-danger-soft hover:text-fg-danger"
            >
                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <button
            type="button"
            x-show="value !== null && formViewUrl"
            @click.stop="openRecord()"
            title="{{ __('Open record') }}"
            aria-label="{{ __('Open record') }}"
            class="pv-m2o-open inline-flex h-6 w-6 shrink-0 items-center justify-center rounded border border-default bg-neutral-primary text-body-subtle transition-colors hover:border-fg-brand hover:text-fg-brand"
        >
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"
                />
            </svg>
        </button>
    </div>

    <div
        x-show="open"
        x-cloak
        x-transition.opacity.duration.100ms
        class="pv-m2o-dropdown absolute left-0 right-0 top-full z-50 mt-1 max-h-72 overflow-auto rounded-md border border-default bg-neutral-primary text-sm shadow-lg"
    >
        <template x-if="loading">
            <div class="px-3 py-2 text-xs text-body-subtle">{{ __('Searching…') }}</div>
        </template>
        <template x-if="!loading && results.length === 0 && !createCandidate && !canCreateAndEdit">
            <div class="px-3 py-2 text-xs text-body-subtle">{{ __('No matches.') }}</div>
        </template>
        <ul x-show="!loading && (results.length > 0 || createCandidate || canCreateAndEdit)" role="listbox">
            <template x-for="(item, i) in results" :key="item.id">
                <li
                    role="option"
                    @click="pick(item)"
                    @mousemove="cursor = i"
                    :class="i === cursor ? 'bg-brand-softer text-fg-brand' : 'text-body hover:bg-neutral-secondary'"
                    class="flex cursor-pointer items-center justify-between px-3 py-1.5"
                >
                    <span x-text="item.label" class="truncate"></span>
                    <span x-show="item.id === value" class="text-2xs font-medium text-fg-brand">{{ __('selected') }}</span>
                </li>
            </template>
            <template x-if="createCandidate">
                <li
                    role="option"
                    @click="createFromQuery()"
                    @mousemove="cursor = results.length"
                    :class="cursor === results.length ? 'bg-success-soft text-success-strong' : 'text-fg-success hover:bg-neutral-secondary'"
                    class="flex cursor-pointer items-center gap-1.5 border-t border-default px-3 py-1.5"
                >
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>{{ __('Create') }} "<span x-text="query.trim()"></span>"</span>
                </li>
            </template>
            <template x-if="canCreateAndEdit">
                <li
                    role="option"
                    @click="createAndEdit()"
                    @mousemove="cursor = createAndEditIndex"
                    :class="cursor === createAndEditIndex ? 'bg-brand-softer text-fg-brand' : 'text-body hover:bg-neutral-secondary'"
                    class="flex cursor-pointer items-center gap-1.5 border-t border-default px-3 py-1.5"
                >
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"
                        />
                    </svg>
                    {{ __('Create and edit…') }}
                </li>
            </template>
        </ul>
    </div>
</div>
