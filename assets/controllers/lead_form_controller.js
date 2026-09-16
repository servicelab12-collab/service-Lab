import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['form', 'button', 'label', 'spinner'];

    submit(event) {
        const form = this.hasFormTarget ? this.formTarget : this.element;
        if (form.checkValidity && !form.checkValidity()) {
            return;
        }

        if (this.hasButtonTarget) {
            this.buttonTarget.disabled = true;
            this.buttonTarget.classList.add('is-loading');
        }
        if (this.hasLabelTarget) {
            this.labelTarget.dataset.original = this.labelTarget.textContent;
            this.labelTarget.textContent = 'Envoi en cours…';
        }
        if (this.hasSpinnerTarget) {
            this.spinnerTarget.hidden = false;
        }
    }
}
