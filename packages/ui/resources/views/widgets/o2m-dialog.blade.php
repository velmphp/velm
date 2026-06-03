@php
    $cfg = [
        'wireKey' => $wireKey,
        'comodel' => $comodel,
        'inverseName' => $inverseName,
        'searchUrl' => $searchUrl,
        'formViewUrl' => $formViewUrl ?? null,
        'rows' => $rows ?? [],
        'parentRecordId' => $parentRecordId ?? null,
        'readonly' => $readonly ?? false,
        'inline' => $inline ?? false,
        'columns' => $columns ?? [['name' => 'name', 'label' => 'Name']],
    ];
@endphp

<div wire:ignore class="pv-o2m-dialog space-y-2" x-data="pvO2mDialog(@js($cfg))">
    @if (! ($parentRecordId ?? null) && ! ($readonly ?? false))
        <p class="text-xs text-body-subtle">
            {{ __('Save the parent record first to link new lines.') }}
        </p>
    @endif

    <div class="overflow-x-auto rounded-lg border border-default">
        <table class="min-w-full divide-y divide-default text-sm">
            <thead class="bg-neutral-secondary">
                <tr>
                    @foreach ($columns as $col)
                        <th class="px-3 py-2 text-start text-2xs font-semibold uppercase tracking-wider text-body-subtle">
                            {{ $col['label'] }}
                        </th>
                    @endforeach
                    @if (! ($readonly ?? false))
                        <th class="w-8 px-3 py-2"></th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-default bg-neutral-primary">
                <template x-for="row in rows" :key="row.id">
                    <tr class="transition-colors hover:bg-neutral-secondary">
                        <template x-for="col in columns" :key="col.name">
                            <td class="px-3 py-2 text-body" x-text="row[col.name] ?? row.label"></td>
                        </template>
                        <td x-show="!readonly" class="px-3 py-2 text-end">
                            <div class="flex items-center justify-end gap-1">
                                <button
                                    type="button"
                                    x-show="formViewUrl"
                                    @click="openRecord(row.id, row.label || row.name)"
                                    class="text-xs text-fg-brand hover:underline"
                                >{{ __('Open') }}</button>
                                <button
                                    type="button"
                                    @click="remove(row.id)"
                                    class="text-body-subtle transition-colors hover:text-fg-danger"
                                    aria-label="{{ __('Remove') }}"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>
                <tr x-show="rows.length === 0">
                    <td :colspan="columns.length + (readonly ? 0 : 1)" class="px-3 py-4 text-center text-xs text-body-subtle">
                        {{ __('No lines yet.') }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div x-show="!readonly && parentRecordId" class="flex flex-wrap items-center gap-2">
        <button
            type="button"
            x-show="formViewUrl"
            @click="createNew()"
            class="inline-flex items-center gap-1 rounded-md bg-brand-soft px-2.5 py-1 text-xs font-medium text-fg-brand hover:bg-brand-softer"
        >
            {{ __('Add line') }}
        </button>
        <button
            type="button"
            @click="linkOpen = true; open = true; $nextTick(() => { searchNow(); $refs.linkInput?.focus(); })"
            class="inline-flex items-center gap-1 rounded-md border border-default bg-neutral-secondary px-2.5 py-1 text-xs font-medium text-body hover:bg-neutral-tertiary"
        >
            {{ __('Link existing…') }}
        </button>
    </div>

    <div x-show="linkOpen && !readonly" class="relative">
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
            class="w-full rounded-lg border border-default bg-neutral-primary px-3 py-2 text-sm"
        />
        <div
            x-show="open"
            x-cloak
            class="absolute start-0 end-0 top-full z-30 mt-1 max-h-48 overflow-auto rounded-md border border-default bg-neutral-primary shadow-lg"
        >
            <template x-for="(opt, i) in filteredResults()" :key="opt.id">
                <button
                    type="button"
                    @click="add(opt)"
                    :class="i === cursor ? 'bg-brand-softer text-fg-brand' : 'text-body hover:bg-neutral-secondary'"
                    class="block w-full px-3 py-1.5 text-start text-sm"
                    x-text="opt.label"
                ></button>
            </template>
        </div>
    </div>
</div>
