@php
    $filterable = $this->filterableListHeaders();
    $groupable = $this->groupableListHeaders();
    $headers = $this->listHeaders();
    $chips = $this->listDisplayChips();
    $listGroupBy = $this->listGroupBy;
    $listOpenFilterField = $this->listOpenFilterField;
    $listM2oResults = $this->listM2oResults;
@endphp

<div
    class="velm-list-toolbar mb-4 space-y-3"
    x-data="{ panelOpen: @entangle('listFiltersPanelOpen'), columnsOpen: @entangle('listColumnsPanelOpen') }"
    x-init="$nextTick(() => $refs.listSearchInput?.focus())"
    @click.outside="panelOpen = false; columnsOpen = false"
>
    <div class="flex items-center justify-between gap-3">
        <div
            class="relative min-w-0 flex-1 rounded-lg border border-default bg-neutral-primary transition-shadow focus-within:border-fg-brand focus-within:ring-2 focus-within:ring-fg-brand"
            data-pv-search-bar
        >
            <div class="flex min-h-[40px] flex-wrap items-center gap-1.5 px-2.5 py-1.5">
                <x-filament::icon
                    icon="heroicon-o-magnifying-glass"
                    class="h-4 w-4 shrink-0 text-body-subtle"
                />

                @foreach ($chips as $chip)
                    <span
                        class="inline-flex max-w-[200px] items-center gap-1 rounded bg-brand-soft py-0.5 pr-1 pl-2 text-xs font-medium text-fg-brand"
                    >
                        <span class="truncate">{{ $chip['label'] }}</span>
                        <button
                            type="button"
                            @if ($chip['field'] === null)
                                wire:click="$set('listSearch', '')"
                            @else
                                wire:click="removeListFilterChipByField('{{ $chip['field'] }}')"
                            @endif
                            aria-label="{{ __('Remove filter') }}"
                            class="ml-0.5 rounded p-0.5 transition-colors hover:bg-fg-brand/20"
                        >
                            <x-filament::icon icon="heroicon-o-x-mark" class="h-3 w-3" />
                        </button>
                    </span>
                @endforeach

                <input
                    type="search"
                    x-ref="listSearchInput"
                    wire:model.live.debounce.400ms="listSearch"
                    autofocus
                    placeholder="{{ count($chips) ? '' : __('Search…') }}"
                    class="min-w-[120px] flex-1 border-0 bg-transparent px-1 py-1 text-sm text-body placeholder:text-body-subtle focus:outline-none focus:ring-0"
                />

                <div wire:loading wire:target="listSearch,listFilterChips,listGroupBy,clearListQuery,addBooleanListFilter,addM2oListFilter" class="shrink-0">
                    <x-filament::loading-indicator class="h-4 w-4 text-fg-brand" />
                </div>

                @if (count($chips) > 0 || filled($listGroupBy))
                    <button
                        type="button"
                        wire:click="clearListQuery"
                        class="shrink-0 px-1 text-2xs text-body-subtle underline hover:text-fg-brand"
                    >
                        {{ __('clear all') }}
                    </button>
                @endif

                <button
                    type="button"
                    @click="columnsOpen = !columnsOpen; if (columnsOpen) panelOpen = false"
                    :class="columnsOpen ? 'border-fg-brand bg-brand-soft text-fg-brand' : 'border-default text-body-subtle hover:text-fg-brand'"
                    class="inline-flex shrink-0 items-center gap-1 rounded border px-2 py-1 text-xs font-medium transition-colors"
                >
                    <x-filament::icon icon="heroicon-o-squares-2x2" class="h-3.5 w-3.5" />
                    <span>{{ __('Columns') }}</span>
                </button>

                <button
                    type="button"
                    @click="panelOpen = !panelOpen; if (panelOpen) columnsOpen = false"
                    :class="panelOpen ? 'border-fg-brand bg-brand-soft text-fg-brand' : 'border-default text-body-subtle hover:text-fg-brand'"
                    class="inline-flex shrink-0 items-center gap-1 rounded border px-2 py-1 text-xs font-medium transition-colors"
                >
                    <x-filament::icon icon="heroicon-o-funnel" class="h-3.5 w-3.5" />
                    <span>{{ __('Filters') }}</span>
                    <x-filament::icon
                        icon="heroicon-o-chevron-down"
                        class="h-3 w-3 transition-transform"
                        x-bind:class="{ 'rotate-180': panelOpen }"
                    />
                </button>
            </div>

            <div
                x-show="columnsOpen"
                x-cloak
                x-transition.opacity.duration.100ms
                @click.outside="columnsOpen = false"
                class="absolute top-full right-0 left-0 z-30 mt-1 max-h-64 overflow-y-auto rounded-lg border border-default bg-neutral-primary p-3 shadow-sm"
            >
                <header class="mb-1 px-2 py-1 text-2xs font-bold tracking-wider text-body-subtle uppercase">
                    {{ __('Show columns') }}
                </header>
                <div class="space-y-0.5">
                    @foreach ($headers as $header)
                        <label
                            class="flex cursor-pointer select-none items-center gap-2 rounded px-2 py-1.5 text-sm text-body hover:bg-neutral-secondary"
                        >
                            <input
                                type="checkbox"
                                class="rounded border-default text-fg-brand focus:ring-fg-brand/40"
                                @checked($this->isListColumnVisible($header['name']))
                                wire:click="toggleListColumn('{{ $header['name'] }}')"
                            >
                            <span>{{ $header['label'] }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div
                x-show="panelOpen"
                x-cloak
                x-transition.opacity.duration.100ms
                class="absolute top-full right-0 left-0 z-30 mt-1 grid grid-cols-1 divide-default rounded-lg border border-default bg-neutral-primary shadow-sm md:grid-cols-2 md:divide-x"
            >
                <section class="space-y-1 p-3">
                    <header class="px-2 py-1 text-2xs font-bold tracking-wider text-body-subtle uppercase">
                        {{ __('Filter By') }}
                    </header>
                    @forelse ($filterable as $header)
                        <div>
                            <button
                                type="button"
                                wire:click="toggleListFilterField('{{ $header['name'] }}')"
                                class="flex w-full items-center justify-between gap-1 rounded px-2 py-1.5 text-sm text-body transition-colors hover:bg-neutral-secondary"
                            >
                                <span>{{ $header['label'] }}</span>
                                <x-filament::icon
                                    icon="heroicon-o-chevron-right"
                                    @class([
                                        'h-3 w-3 text-body-subtle transition-transform',
                                        'rotate-90' => $listOpenFilterField === $header['name'],
                                    ])
                                />
                            </button>

                            @if ($header['filter_kind'] === 'boolean' && $listOpenFilterField === $header['name'])
                                <div class="space-y-1 py-1 pl-3">
                                    <button
                                        type="button"
                                        wire:click="addBooleanListFilter('{{ $header['name'] }}', true)"
                                        class="w-full rounded px-2 py-1 text-left text-sm text-body hover:bg-neutral-secondary"
                                    >
                                        {{ __('Yes') }}
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="addBooleanListFilter('{{ $header['name'] }}', false)"
                                        class="w-full rounded px-2 py-1 text-left text-sm text-body hover:bg-neutral-secondary"
                                    >
                                        {{ __('No') }}
                                    </button>
                                </div>
                            @endif

                            @if ($header['filter_kind'] === 'm2o' && $listOpenFilterField === $header['name'])
                                <div class="space-y-1 py-1 pl-3" wire:key="m2o-filter-{{ $header['name'] }}">
                                    <input
                                        type="search"
                                        placeholder="{{ __('Search…') }}"
                                        wire:model.live.debounce.250ms="listM2oQuery.{{ $header['name'] }}"
                                        wire:keydown.debounce.250ms="searchListM2o('{{ $header['name'] }}')"
                                        class="w-full rounded border border-default bg-neutral-primary px-2 py-1 text-sm text-body placeholder:text-body-subtle focus:border-fg-brand focus:ring-1 focus:ring-fg-brand/40 focus:outline-none"
                                    >
                                    @foreach ($listM2oResults[$header['name']] ?? [] as $option)
                                        <button
                                            type="button"
                                            wire:click="addM2oListFilter('{{ $header['name'] }}', {{ $option['id'] }}, @js($option['label']))"
                                            class="w-full truncate rounded px-2 py-1 text-left text-sm text-body hover:bg-neutral-secondary"
                                        >
                                            {{ $option['label'] }}
                                        </button>
                                    @endforeach
                                    @if (empty($listM2oResults[$header['name']] ?? []))
                                        <p class="px-2 py-1 text-2xs text-body-subtle">{{ __('Type to search.') }}</p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="px-2 py-1 text-xs text-body-subtle">{{ __('No filterable columns.') }}</p>
                    @endforelse
                </section>

                <section class="space-y-1 p-3">
                    <header class="px-2 py-1 text-2xs font-bold tracking-wider text-body-subtle uppercase">
                        {{ __('Group By') }}
                    </header>
                    @forelse ($groupable as $header)
                        <button
                            type="button"
                            wire:click="toggleListGroupBy('{{ $header['name'] }}')"
                            @class([
                                'flex w-full items-center justify-between gap-1 rounded px-2 py-1.5 text-sm transition-colors',
                                'bg-brand-soft text-fg-brand' => $listGroupBy === $header['name'],
                                'text-body hover:bg-neutral-secondary' => $listGroupBy !== $header['name'],
                            ])
                        >
                            <span>{{ $header['label'] }}</span>
                            @if ($listGroupBy === $header['name'])
                                <x-filament::icon icon="heroicon-o-check" class="h-3.5 w-3.5" />
                            @endif
                        </button>
                    @empty
                        <p class="px-2 py-1 text-xs text-body-subtle">{{ __('No groupable columns.') }}</p>
                    @endforelse
                </section>
            </div>
        </div>
    </div>

    @if (filled($listGroupBy))
        <div class="flex items-center gap-1.5">
            <span class="text-2xs tracking-wider text-body-subtle uppercase">{{ __('Grouped by') }}</span>
            <span class="inline-flex items-center gap-1 rounded bg-warning-soft py-0.5 pr-1 pl-2 text-xs font-medium text-warning-strong">
                <span>{{ $this->listGroupByLabel() }}</span>
                <button
                    type="button"
                    wire:click="setListGroupBy(null)"
                    aria-label="{{ __('Remove grouping') }}"
                    class="ml-0.5 rounded p-0.5 transition-colors hover:bg-warning-strong/20"
                >
                    <x-filament::icon icon="heroicon-o-x-mark" class="h-3 w-3" />
                </button>
            </span>
        </div>
    @endif
</div>
