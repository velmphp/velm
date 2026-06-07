import ApexCharts from 'apexcharts';

window.ApexCharts = ApexCharts;

document.addEventListener('alpine:init', () => {
    Alpine.data('pvGraphToolbar', (cfg) => ({
        model: cfg.model,
        module: cfg.module || '',
        view: cfg.view || '',
        groupby: cfg.initGroupby,
        measure: cfg.initMeasure,
        chartType: cfg.initChart || 'bar',
        groupable: cfg.groupable || [],
        measurable: cfg.measurable || [],
        searchText: cfg.search || '',
        labels: cfg.initLabels || [],
        values: cfg.initValues || [],
        measureLabel: cfg.initMeasureLabel || '',
        loading: false,
        _chart: null,

        chartTypes: [
            {
                value: 'bar',
                label: 'Bar',
                icon: '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>',
            },
            {
                value: 'line',
                label: 'Line',
                icon: '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l5-5 4 4 5-5 4 4"/></svg>',
            },
            {
                value: 'area',
                label: 'Area',
                icon: '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2 17l5-8 4 5 3-3 4 4 4-6"/><path stroke-linecap="round" stroke-linejoin="round" d="M2 17h20" stroke-dasharray="2"/></svg>',
            },
            {
                value: 'pie',
                label: 'Pie',
                icon: '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6.5a6 6 0 11-6 6h6V6.5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6a6 6 0 016 6"/></svg>',
            },
        ],

        get subtitle() {
            return this.labels.length
                ? `${this.labels.length} group${this.labels.length !== 1 ? 's' : ''}`
                : '';
        },

        init() {
            this.$nextTick(() => {
                this.$refs.searchInput?.focus({ preventScroll: true });
                this.renderChart();
            });
            window.addEventListener('resize', (this._onResize = () => this.renderChart()));
        },

        destroy() {
            window.removeEventListener('resize', this._onResize);
            if (this._chart) {
                this._chart.destroy();
                this._chart = null;
            }
        },

        setChartType(ct) {
            this.chartType = ct;
            this.renderChart();
        },

        async fetchData() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    model: this.model,
                    groupby: this.groupby,
                    measure: this.measure,
                    chart: this.chartType,
                    search: this.searchText,
                });

                if (this.module) {
                    params.set('module', this.module);
                }

                if (this.view) {
                    params.set('view', this.view);
                }

                const response = await fetch(`/api/graph/data?${params}`, { credentials: 'same-origin' });

                if (!response.ok) {
                    throw new Error('fetch failed');
                }

                const data = await response.json();
                this.labels = data.labels;
                this.values = data.values;
                this.measureLabel = data.measure_label;
            } catch {
                window.pvAlert?.('Could not load chart data.', { variant: 'warning' });
            } finally {
                this.loading = false;
                await this.$nextTick();
                this.renderChart();
            }
        },

        themeVar(name, fallback) {
            const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();

            return value || fallback;
        },

        renderChart() {
            if (typeof ApexCharts === 'undefined') {
                setTimeout(() => this.renderChart(), 60);

                return;
            }

            const el = this.$refs.mount;

            if (!el) {
                return;
            }

            if (this._chart) {
                this._chart.destroy();
                this._chart = null;
            }

            if (!this.values.length) {
                return;
            }

            const isDark = document.documentElement.classList.contains('dark');
            const fg = this.themeVar('--color-body', isDark ? '#e5e7eb' : '#1f2937');
            const fgSubtle = this.themeVar('--color-body-subtle', isDark ? '#9ca3af' : '#6b7280');
            const brand = this.themeVar('--color-fg-brand', '#2563eb');
            const gridClr = this.themeVar('--color-border-default', isDark ? '#374151' : '#e5e7eb');
            const palette = [brand, '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899', '#84cc16'];

            const apexType =
                this.chartType === 'pie'
                    ? 'pie'
                    : this.chartType === 'line'
                      ? 'line'
                      : this.chartType === 'area'
                        ? 'area'
                        : 'bar';

            const base = {
                chart: {
                    type: apexType,
                    height: 400,
                    background: 'transparent',
                    toolbar: { show: false },
                    animations: { speed: 220 },
                    foreColor: fg,
                },
                theme: { mode: isDark ? 'dark' : 'light' },
                colors: palette,
                grid: { borderColor: gridClr },
                tooltip: { theme: isDark ? 'dark' : 'light' },
                legend: { labels: { colors: fgSubtle } },
            };

            let opts;

            if (apexType === 'pie') {
                opts = {
                    ...base,
                    series: this.values,
                    labels: this.labels,
                    dataLabels: { enabled: true },
                };
            } else {
                opts = {
                    ...base,
                    series: [{ name: this.measureLabel, data: this.values }],
                    xaxis: { categories: this.labels, labels: { style: { colors: fgSubtle } } },
                    yaxis: { labels: { style: { colors: fgSubtle } } },
                    dataLabels: { enabled: false },
                    plotOptions: { bar: { horizontal: false, borderRadius: 4 } },
                    fill:
                        apexType === 'area'
                            ? {
                                  type: 'gradient',
                                  gradient: { shadeIntensity: 1, opacityFrom: 0.55, opacityTo: 0.05 },
                              }
                            : {},
                    stroke:
                        apexType === 'line' || apexType === 'area'
                            ? { curve: 'smooth', width: 2 }
                            : { width: 0 },
                };
            }

            this._chart = new ApexCharts(el, opts);
            this._chart.render();
        },
    }));
});
