import { Controller } from '@hotwired/stimulus';

/** Accordion polish: only one FAQ open at a time (optional UX). */
export default class extends Controller {
    connect() {
        this.element.querySelectorAll('details.faq-item').forEach((item) => {
            item.addEventListener('toggle', () => {
                if (!item.open) return;
                this.element.querySelectorAll('details.faq-item').forEach((other) => {
                    if (other !== item) other.open = false;
                });
            });
        });
    }
}
