@php
    $config = $this->pivotToolbarConfig();
    $pivot = $config['initial'];
@endphp

<div
    class="space-y-4"
    data-velm-breadcrumb-trail="{{ $this->velmBreadcrumbTrailJson() }}"
    data-velm-nav-label="{{ $this->velmNavLabel() }}"
>
    @include('velm-ui::partials.breadcrumbs')

    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-lg font-semibold text-heading">{{ $this->getTitle() }}</h1>
    </div>

    <div
        x-data="pvPivotToolbar(@js(collect($config)->except('initial')->all()))"
        class="flex flex-col gap-3"
    >
        <div class="flex flex-wrap items-center gap-2">
            @include('velm-admin::pages.analytics.partials.view-switcher')

            <div class="mx-1 hidden h-5 w-px bg-default sm:block"></div>

            <div class="flex items-center gap-1 text-xs">
                <span class="whitespace-nowrap text-body-subtle">{{ __('Rows') }}</span>
                <div class="flex flex-wrap items-center gap-1">
                    <template x-for="(spec, index) in rowGroupby" :key="index">
                        <span class="inline-flex items-center gap-0.5 rounded-full bg-brand-soft px-2 py-0.5 text-xs font-medium text-fg-brand">
                            <span x-text="labelFor(spec)"></span>
                            <button
                                @click="removeRowGroupby(index)"
                                type="button"
                                class="ml-0.5 hover:opacity-70 focus:outline-none"
                                title="{{ __('Remove') }}"
                            >×</button>
                        </span>
                    </template>
                    <select
                        @change="addRowGroupby($event.target.value); $event.target.value = ''"
                        class="rounded border border-default bg-surface py-0.5 pl-1.5 pr-5 text-xs text-body focus:outline-none focus:ring-2 focus:ring-fg-brand/40"
                    >
                        <option value="">{{ __('+ Add…') }}</option>
                        <template x-for="field in groupable" :key="field.value">
                            <option :value="field.value" x-text="field.label"></option>
                        </template>
                    </select>
                </div>
            </div>

            <div class="mx-0.5 hidden h-5 w-px bg-default sm:block"></div>

            <button
                @click="swapAxes()"
                type="button"
                title="{{ __('Swap rows and columns') }}"
                class="rounded border border-default p-1.5 text-body-subtle hover:bg-surface-muted focus:outline-none focus:ring-2 focus:ring-fg-brand/40"
            >
                <x-velm-ui::icon icon="heroicon-o-arrows-up-down" class="h-3.5 w-3.5" />
            </button>

            <div class="mx-0.5 hidden h-5 w-px bg-default sm:block"></div>

            <div class="flex items-center gap-1 text-xs">
                <span class="whitespace-nowrap text-body-subtle">{{ __('Cols') }}</span>
                <div class="flex flex-wrap items-center gap-1">
                    <template x-for="(spec, index) in colGroupby" :key="index">
                        <span class="inline-flex items-center gap-0.5 rounded-full border border-default bg-surface-muted px-2 py-0.5 text-xs font-medium text-body">
                            <span x-text="labelFor(spec)"></span>
                            <button
                                @click="removeColGroupby(index)"
                                type="button"
                                class="ml-0.5 hover:opacity-70 focus:outline-none"
                                title="{{ __('Remove') }}"
                            >×</button>
                        </span>
                    </template>
                    <select
                        @change="addColGroupby($event.target.value); $event.target.value = ''"
                        class="rounded border border-default bg-surface py-0.5 pl-1.5 pr-5 text-xs text-body focus:outline-none focus:ring-2 focus:ring-fg-brand/40"
                    >
                        <option value="">{{ __('+ Add…') }}</option>
                        <template x-for="field in groupable" :key="field.value">
                            <option :value="field.value" x-text="field.label"></option>
                        </template>
                    </select>
                </div>
            </div>

            <div class="mx-0.5 hidden h-5 w-px bg-default sm:block"></div>

            <div class="flex items-center gap-1 text-xs">
                <span class="whitespace-nowrap text-body-subtle">{{ __('Measures') }}</span>
                <div class="flex flex-wrap items-center gap-1">
                    <template x-for="(spec, index) in measures" :key="index">
                        <span class="inline-flex items-center gap-0.5 rounded-full border border-default bg-surface-muted px-2 py-0.5 text-xs font-medium text-body">
                            <span x-text="measureLabelFor(spec)"></span>
                            <button
                                @click="removeMeasure(index)"
                                type="button"
                                x-show="measures.length > 1"
                                class="ml-0.5 hover:opacity-70 focus:outline-none"
                                title="{{ __('Remove') }}"
                            >×</button>
                        </span>
                    </template>
                    <select
                        @change="addMeasure($event.target.value); $event.target.value = ''"
                        class="rounded border border-default bg-surface py-0.5 pl-1.5 pr-5 text-xs text-body focus:outline-none focus:ring-2 focus:ring-fg-brand/40"
                    >
                        <option value="">{{ __('+ Add…') }}</option>
                        <template x-for="field in measurable" :key="field.value">
                            <option :value="field.value" x-text="field.label"></option>
                        </template>
                    </select>
                </div>
            </div>

            <form @submit.prevent="fetchData()" class="flex min-w-[130px] flex-1 items-center">
                <div class="pv-search-bar-field flex min-h-[32px] min-w-[130px] max-w-xs flex-1 items-center gap-1.5 px-2.5 py-1">
                    <x-velm-ui::icon
                        icon="heroicon-o-magnifying-glass"
                        class="h-3.5 w-3.5 shrink-0 text-body-subtle"
                    />
                    <input
                        type="search"
                        x-ref="searchInput"
                        x-model="searchText"
                        placeholder="{{ __('Search…') }}"
                        class="min-w-[80px] flex-1 border-0 bg-transparent px-1 py-0.5 text-xs text-body placeholder:text-body-subtle focus:outline-none focus:ring-0"
                    />
                </div>
            </form>

            <span x-show="loading" x-cloak>
                <svg class="h-4 w-4 animate-spin text-body-subtle" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
            </span>
        </div>

        <div class="overflow-auto rounded-lg border border-default bg-surface">
            <div x-show="!hasFetched" x-ref="serverTable">
                @include('velm-admin::pages.analytics.partials.pivot-table', ['pivot' => $pivot])
            </div>
            <div x-show="hasFetched" x-ref="dynamicTable" x-html="tableHtml"></div>
        </div>
    </div>
</div>

@push('scripts')
    <script defer src="{{ \Velm\Ui\UiAssets::pivotScriptHref() }}"></script>
@endpush
