import { Controller } from '@hotwired/stimulus';

/** Animated KPI counters when section enters viewport. */
export default class extends Controller {
    static targets = ['value'];

    connect() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            this.valueTargets.forEach((el) => {
                const to = Number(el.dataset.to || 0);
                const suffix = el.dataset.suffix || '';
                el.textContent = `${to}${suffix}`;
            });
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                this.animateAll();
                observer.disconnect();
            });
        }, { threshold: 0.4 });

        observer.observe(this.element);
    }

    animateAll() {
        this.valueTargets.forEach((el) => {
            const to = Number(el.dataset.to || 0);
            const suffix = el.dataset.suffix || '';
            const duration = 1100;
            const start = performance.now();

            const tick = (now) => {
                const progress = Math.min(1, (now - start) / duration);
                const eased = 1 - Math.pow(1 - progress, 3);
                el.textContent = `${Math.round(to * eased)}${suffix}`;
                if (progress < 1) requestAnimationFrame(tick);
            };

            requestAnimationFrame(tick);
        });
    }
}
