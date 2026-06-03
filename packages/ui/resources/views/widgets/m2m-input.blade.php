@php
    $cfg = [
        'wireKey' => $wireKey,
        'comodel' => $comodel,
        'searchUrl' => $searchUrl,
        'formViewUrl' => $formViewUrl ?? null,
        'initial' => $initial ?? [],
        'readonly' => $readonly ?? false,
        'dialogOnly' => $dialogOnly ?? false,
        'canQuickCreate' => $canQuickCreate ?? false,
    ];
@endphp

<div
    wire:ignore
    class="pv-m2m"
    x-data="pvM2m(@js($cfg))"
    @click.outside="open = false"
    @keydown.escape.prevent.stop="open = false"
>
    <div
        class="flex flex-wrap items-center gap-1 rounded-lg border border-default bg-neutral-primary px-2 py-1.5 pv-focus-within"
    >
        <template x-for="item in selected" :key="item.id">
            <span class="pv-chip max-w-[200px]">
                <button
                    type="button"
                    x-show="formViewUrl"
                    x-text="item.label"
                    class="truncate text-start text-fg-brand hover:underline"
                    @click.stop="openChip(item)"
                ></button>
                <span x-show="!formViewUrl" x-text="item.label" class="truncate"></span>
                <button
                    type="button"
                    x-show="!readonly"
                    @click.stop="remove(item.id)"
                    aria-label="{{ __('Remove') }}"
                    class="ms-0.5 shrink-0 rounded p-0.5 transition-colors hover:bg-fg-brand/20"
                >
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </span>
        </template>

        <input
            type="text"
            x-show="!dialogOnly"
            x-ref="input"
            x-model="query"
            :readonly="readonly"
            @focus="onFocus()"
            @input.debounce.200ms="searchNow()"
            @keydown.arrow-down.prevent="moveCursor(1)"
            @keydown.arrow-up.prevent="moveCursor(-1)"
            @keydown.enter.prevent="onEnter()"
            :placeholder="selected.length ? '' : '{{ __('Add…') }}'"
            autocomplete="off"
            class="min-w-[80px] flex-1 border-0 bg-transparent p-0 text-sm text-body placeholder:text-body-subtle focus:outline-none focus:ring-0"
        />
    </div>

    <div x-show="dialogOnly && !readonly" class="mt-2 flex flex-wrap items-center gap-2">
        <button
            type="button"
            x-show="formViewUrl"
            @click="createAndEdit()"
            class="inline-flex items-center gap-1 rounded-md bg-brand-soft px-2.5 py-1 text-xs font-medium text-fg-brand transition-colors hover:bg-brand-softer"
        >
            {{ __('Create new') }}
        </button>
        <button
            type="button"
            @click="linkOpen = true; open = true; $nextTick(() => { searchNow(); $refs.linkInput?.focus(); })"
            class="inline-flex items-center gap-1 rounded-md border border-default bg-neutral-secondary px-2.5 py-1 text-xs font-medium text-body transition-colors hover:bg-neutral-tertiary"
        >
            {{ __('Link existing…') }}
        </button>
    </div>

    <div x-show="dialogOnly && linkOpen && !readonly" class="mt-2">
        <input
            type="text"
            x-ref="linkInput"
            x-model="query"
            @input.debounce.200ms="searchNow()"
            @keydown.arrow-down.prevent="moveCursor(1)"
            @keydown.arrow-up.prevent="moveCursor(-1)"
            @keydown.enter.prevent="onEnter()"
            @keydown.escape.prevent.stop="linkOpen = false; open = false"
            placeholder="{{ __('Search to link…') }}"
            autocomplete="off"
            class="pv-input"
        />
    </div>

    <div x-show="open && (!dialogOnly || linkOpen)" x-cloak x-transition class="relative">
        <div class="pv-dropdown start-0 end-0 top-1 max-h-60 overflow-auto text-sm">
            <template x-if="loading">
                <div class="px-3 py-2 text-xs text-body-subtle">{{ __('Searching…') }}</div>
            </template>
            <ul role="listbox">
                <template x-for="(opt, i) in filteredResults()" :key="opt.id">
                    <li
                        role="option"
                        @click="add(opt)"
                        @mousemove="cursor = i"
                        :class="i === cursor ? 'pv-dropdown-item-active' : ''"
                        class="pv-dropdown-item truncate"
                        x-text="opt.label"
                    ></li>
                </template>
                <template x-if="createCandidate">
                    <li
                        role="option"
                        @click="createFromQuery()"
                        class="pv-dropdown-item flex cursor-pointer items-center gap-1.5 border-t border-default text-fg-brand hover:bg-brand-soft"
                    >
                        {{ __('Create') }} "<span x-text="query.trim()"></span>"
                    </li>
                </template>
            </ul>
        </div>
    </div>
</div>
