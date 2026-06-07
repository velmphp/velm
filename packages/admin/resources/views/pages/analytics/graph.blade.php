@php($config = $this->graphToolbarConfig())

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
        x-data="pvGraphToolbar(@js($config))"
        class="flex flex-col gap-3"
    >
        <div class="flex flex-wrap items-center gap-2">
            @include('velm-admin::pages.analytics.partials.view-switcher')

            <div class="mx-1 hidden h-5 w-px bg-default sm:block"></div>

            <div class="inline-flex overflow-hidden rounded-md border border-default text-xs">
                <template x-for="ct in chartTypes" :key="ct.value">
                    <button
                        type="button"
                        @click="setChartType(ct.value)"
                        :title="ct.label"
                        :class="chartType === ct.value
                            ? 'bg-brand-soft font-semibold text-fg-brand'
                            : 'text-body hover:bg-surface-muted'"
                        class="inline-flex items-center gap-1 border-r border-default px-2.5 py-1 transition-colors last:border-r-0"
                    >
                        <span x-html="ct.icon"></span>
                        <span x-text="ct.label"></span>
                    </button>
                </template>
            </div>

            <div class="flex items-center gap-1 text-xs">
                <span class="whitespace-nowrap text-body-subtle">{{ __('Group by') }}</span>
                <select
                    x-model="groupby"
                    @change="fetchData()"
                    class="rounded border border-default bg-surface py-1 pl-2 pr-6 text-xs text-body focus:border-fg-brand focus:outline-none focus:ring-2 focus:ring-fg-brand/40"
                >
                    <template x-for="field in groupable" :key="field.value">
                        <option :value="field.value" x-text="field.label"></option>
                    </template>
                </select>
            </div>

            <div class="flex items-center gap-1 text-xs">
                <span class="whitespace-nowrap text-body-subtle">{{ __('Measure') }}</span>
                <select
                    x-model="measure"
                    @change="fetchData()"
                    class="rounded border border-default bg-surface py-1 pl-2 pr-6 text-xs text-body focus:border-fg-brand focus:outline-none focus:ring-2 focus:ring-fg-brand/40"
                >
                    <template x-for="field in measurable" :key="field.value">
                        <option :value="field.value" x-text="field.label"></option>
                    </template>
                </select>
            </div>

            <form @submit.prevent="fetchData()" class="flex min-w-[140px] flex-1 items-center">
                <div class="pv-search-bar-field flex min-h-[32px] min-w-[140px] max-w-xs flex-1 items-center gap-1.5 px-2.5 py-1">
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

            <span x-show="loading" x-cloak class="text-body-subtle">
                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
            </span>
            <span
                x-show="!loading"
                x-text="subtitle"
                class="ml-auto whitespace-nowrap text-xs text-body-subtle"
            ></span>
        </div>

        <div class="rounded-lg border border-default bg-surface p-4">
            <template x-if="values.length === 0 && !loading">
                <div class="py-14 text-center text-body-subtle">
                    <x-velm-ui::icon icon="heroicon-o-chart-bar" class="mx-auto mb-3 h-12 w-12 opacity-40" />
                    <p class="text-sm">{{ __('No data matches. Adjust search or controls.') }}</p>
                </div>
            </template>
            <div
                x-show="values.length > 0 || loading"
                x-ref="mount"
                class="w-full"
                style="min-height: 380px;"
            ></div>
        </div>
    </div>
</div>

@push('scripts')
    <script defer src="{{ \Velm\Ui\UiAssets::graphScriptHref() }}"></script>
@endpush
