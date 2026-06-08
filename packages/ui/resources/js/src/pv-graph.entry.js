import ApexCharts from 'apexcharts';

import { buildApexChartOptions, renderApexChart } from './apex-chart.js';

window.ApexCharts = ApexCharts;

function mountApexChart(component, height = 400) {
    if (typeof ApexCharts === 'undefined') {
        setTimeout(() => mountApexChart(component, height), 60);

        return;
    }

    const el = component.$refs.mount;

    if (!el) {
        return;
    }

    if (component._chart) {
        component._chart.destroy();
        component._chart = null;
    }

    if (!component.values.length) {
        return;
    }

    const options = buildApexChartOptions({
        labels: component.labels,
        values: component.values,
        measureLabel: component.measureLabel,
        chartType: component.chartType,
        height,
    });

    component._chart = renderApexChart(el, options, ApexCharts);
}

document.addEventListener('alpine:init', () => {
    Alpine.data('pvDashboardChart', (cfg) => ({
        labels: cfg.labels || [],
        values: cfg.values || [],
        measureLabel: cfg.measureLabel || '',
        chartType: cfg.chartType || 'bar',
        height: cfg.height || 240,
        _chart: null,
        _onResize: null,
        _onTheme: null,

        get hasData() {
            return this.values.length > 0;
        },

        init() {
            this.$nextTick(() => this.renderChart());
            this._onResize = () => this.renderChart();
            this._onTheme = () => this.renderChart();
            window.addEventListener('resize', this._onResize);
            document.addEventListener('velm:theme-changed', this._onTheme);
        },

        destroy() {
            window.removeEventListener('resize', this._onResize);
            document.removeEventListener('velm:theme-changed', this._onTheme);

            if (this._chart) {
                this._chart.destroy();
                this._chart = null;
            }
        },

        renderChart() {
            mountApexChart(this, this.height);
        },
    }));

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
            document.addEventListener('velm:theme-changed', (this._onTheme = () => this.renderChart()));
        },

        destroy() {
            window.removeEventListener('resize', this._onResize);
            document.removeEventListener('velm:theme-changed', this._onTheme);

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

        renderChart() {
            mountApexChart(this, 400);
        },
    }));
});
