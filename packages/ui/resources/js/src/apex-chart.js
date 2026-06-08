const CHART_PALETTE = ['#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899', '#84cc16'];

export function themeVar(name, fallback) {
    const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();

    return value || fallback;
}

/**
 * @param {{
 *   labels: string[],
 *   values: number[],
 *   measureLabel?: string,
 *   chartType?: string,
 *   height?: number,
 * }} config
 */
export function buildApexChartOptions(config) {
    const labels = Array.isArray(config.labels) ? config.labels : [];
    const values = Array.isArray(config.values) ? config.values : [];
    const measureLabel = config.measureLabel || 'Count';
    const chartType = config.chartType || 'bar';
    const height = config.height || 320;
    const isDark = document.documentElement.classList.contains('dark');
    const fg = themeVar('--color-body', isDark ? '#e5e7eb' : '#1f2937');
    const fgSubtle = themeVar('--color-body-subtle', isDark ? '#9ca3af' : '#6b7280');
    const brand = themeVar('--color-fg-brand', '#2563eb');
    const gridClr = themeVar('--color-border-default', isDark ? '#374151' : '#e5e7eb');
    const palette = [brand, ...CHART_PALETTE.filter((color) => color !== brand)];

    const apexType =
        chartType === 'pie'
            ? 'pie'
            : chartType === 'line'
              ? 'line'
              : chartType === 'area'
                ? 'area'
                : 'bar';

    const base = {
        chart: {
            type: apexType,
            height,
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

    if (apexType === 'pie') {
        return {
            ...base,
            series: values,
            labels,
            dataLabels: { enabled: true },
        };
    }

    return {
        ...base,
        series: [{ name: measureLabel, data: values }],
        xaxis: { categories: labels, labels: { style: { colors: fgSubtle } } },
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

/**
 * @param {HTMLElement} el
 * @param {ReturnType<typeof buildApexChartOptions>} options
 * @param {import('apexcharts').default | undefined} ApexCharts
 */
export function renderApexChart(el, options, ApexCharts) {
    if (!el || typeof ApexCharts === 'undefined' || !options) {
        return null;
    }

    const chart = new ApexCharts(el, options);
    chart.render();

    return chart;
}
