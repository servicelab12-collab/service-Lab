import { Controller } from '@hotwired/stimulus';

/** Visual machine cards that fill the hidden/select field. */
export default class extends Controller {
    static targets = ['input', 'card'];

    connect() {
        const current = this.inputTarget.value;
        if (current) {
            this.highlight(current);
        }
    }

    select(event) {
        const button = event.currentTarget;
        const value = button.dataset.value;
        if (!value) {
            return;
        }

        this.inputTarget.value = value;
        this.inputTarget.dispatchEvent(new Event('change', { bubbles: true }));
        this.highlight(value);
    }

    highlight(value) {
        this.cardTargets.forEach((card) => {
            card.classList.toggle('is-selected', card.dataset.value === value);
            card.setAttribute('aria-pressed', card.dataset.value === value ? 'true' : 'false');
        });
    }
}
