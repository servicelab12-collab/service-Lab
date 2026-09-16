import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        const items = Array.from(this.element.querySelectorAll('[data-reveal]'));

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            items.forEach((el) => el.classList.add('is-visible'));
            return;
        }

        // Immediate hero reveals feel snappier
        items.forEach((el, index) => {
            if (this.element.classList.contains('hero') || el.closest('.hero')) {
                const delay = Number(el.dataset.revealDelay || index * 80);
                window.setTimeout(() => el.classList.add('is-visible'), delay + 60);
            }
        });

        if (!('IntersectionObserver' in window)) {
            items.forEach((el) => el.classList.add('is-visible'));
            return;
        }

        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) return;
                    const el = entry.target;
                    if (el.classList.contains('is-visible')) {
                        observer.unobserve(el);
                        return;
                    }
                    const delay = Number(el.dataset.revealDelay || 0);
                    window.setTimeout(() => el.classList.add('is-visible'), delay);
                    observer.unobserve(el);
                });
            },
            { threshold: 0.12, rootMargin: '0px 0px -30px 0px' }
        );

        items.forEach((el) => {
            if (!el.classList.contains('is-visible')) {
                observer.observe(el);
            }
        });
    }
}
