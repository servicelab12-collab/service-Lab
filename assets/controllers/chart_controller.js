import { Controller } from '@hotwired/stimulus';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

/**
 * Charts start empty, then grow when the block scrolls into view
 * (and after any parent reveal becomes visible).
 */
export default class extends Controller {
    static values = {
        type: { type: String, default: 'bar' },
        labels: Array,
        values: Array,
        label: { type: String, default: '' },
        colors: { type: Array, default: ['#2F5BFF', '#16a34a', '#7c3aed', '#f59e0b', '#0ea5e9', '#ef4444'] },
    };

    connect() {
        this.canvas = this.element.tagName === 'CANVAS'
            ? this.element
            : this.element.querySelector('canvas');

        if (!this.canvas) {
            return;
        }

        this.finalValues = this.valuesValue.map((v) => Number(v) || 0);
        this.played = false;
        this.inView = false;
        this.revealReady = true;

        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        this.mountChart(reduce ? this.finalValues : this.finalValues.map(() => 0), false);
        this.element.classList.add('is-chart-ready');

        if (reduce) {
            this.played = true;
            return;
        }

        this.revealParent = this.element.closest('[data-reveal]');
        if (this.revealParent && !this.revealParent.classList.contains('is-visible')) {
            this.revealReady = false;
            this.mutationObserver = new MutationObserver(() => {
                if (this.revealParent.classList.contains('is-visible')) {
                    this.revealReady = true;
                    this.mutationObserver?.disconnect();
                    this.tryPlay();
                }
            });
            this.mutationObserver.observe(this.revealParent, {
                attributes: true,
                attributeFilter: ['class'],
            });
        }

        this.observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                this.inView = entry.isIntersecting;
                if (entry.isIntersecting) {
                    this.tryPlay();
                }
            });
        }, {
            threshold: 0.4,
            rootMargin: '0px 0px -12% 0px',
        });

        this.observer.observe(this.element);
    }

    tryPlay() {
        if (this.played || !this.inView || !this.revealReady || !this.chart) {
            return;
        }

        this.played = true;
        window.setTimeout(() => this.animateToFinal(), 80);
    }

    animateToFinal() {
        if (!this.chart) {
            return;
        }

        const isDoughnut = this.typeValue === 'doughnut' || this.typeValue === 'pie';

        this.chart.options.animation = {
            duration: isDoughnut ? 1600 : 1400,
            easing: 'easeOutCubic',
            delay: (context) => {
                if (context.type !== 'data' || context.mode !== 'default') {
                    return 0;
                }
                return context.dataIndex * (isDoughnut ? 120 : 140);
            },
            animateRotate: true,
            animateScale: isDoughnut,
        };

        this.chart.data.datasets[0].data = [...this.finalValues];
        this.chart.update();
        this.element.classList.add('is-chart-playing');
    }

    mountChart(data, animate) {
        if (!this.canvas || this.chart) {
            return;
        }

        const colors = this.colorsValue;
        const isDoughnut = this.typeValue === 'doughnut' || this.typeValue === 'pie';
        const isLine = this.typeValue === 'line';
        const isBar = this.typeValue === 'bar';
        const ctx = this.canvas.getContext('2d');

        let backgroundColor;
        if (isDoughnut) {
            backgroundColor = data.map((_, i) => colors[i % colors.length]);
        } else if (isLine) {
            const gradient = ctx.createLinearGradient(0, 0, 0, 220);
            gradient.addColorStop(0, 'rgba(47, 91, 255, 0.35)');
            gradient.addColorStop(1, 'rgba(47, 91, 255, 0.02)');
            backgroundColor = gradient;
        } else {
            const gradient = ctx.createLinearGradient(0, 0, 0, 220);
            gradient.addColorStop(0, '#5B7CFF');
            gradient.addColorStop(1, '#1F4AE0');
            backgroundColor = gradient;
        }

        this.chart = new Chart(ctx, {
            type: this.typeValue,
            data: {
                labels: this.labelsValue,
                datasets: [{
                    label: this.labelValue,
                    data,
                    backgroundColor,
                    borderColor: isLine ? '#2F5BFF' : (isDoughnut ? '#ffffff' : '#1F4AE0'),
                    borderWidth: isDoughnut ? 3 : (isLine ? 3 : 0),
                    fill: isLine,
                    tension: 0.4,
                    borderRadius: isBar ? 10 : 0,
                    borderSkipped: false,
                    pointRadius: isLine ? 4 : 0,
                    pointHoverRadius: isLine ? 7 : 0,
                    pointBackgroundColor: '#2F5BFF',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    hoverOffset: isDoughnut ? 16 : 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: animate,
                interaction: {
                    mode: 'nearest',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        display: isDoughnut,
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            usePointStyle: true,
                            padding: 16,
                            font: { size: 12, weight: '600' },
                            color: '#475569',
                        },
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.92)',
                        padding: 10,
                        cornerRadius: 10,
                        titleFont: { size: 12, weight: '700' },
                        bodyFont: { size: 12 },
                    },
                },
                scales: isDoughnut ? {} : {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#64748b', font: { size: 11, weight: '600' } },
                        border: { display: false },
                    },
                    y: {
                        beginAtZero: true,
                        suggestedMax: Math.max(...this.finalValues, 1) * 1.25,
                        grid: { color: 'rgba(15, 23, 42, 0.06)' },
                        ticks: { color: '#64748b', font: { size: 11 }, precision: 0 },
                        border: { display: false },
                    },
                },
                onHover: (_event, elements, chart) => {
                    chart.canvas.style.cursor = elements.length ? 'pointer' : 'default';
                },
            },
        });
    }

    disconnect() {
        this.observer?.disconnect();
        this.mutationObserver?.disconnect();
        this.chart?.destroy();
        this.chart = null;
    }
}
