import { Controller } from '@hotwired/stimulus';

/**
 * Offer form: exclusive modes — monthly rent OR fixed commission.
 */
export default class extends Controller {
    static targets = ['equalsRent', 'fixedAmount', 'fixedWrap', 'hint'];

    connect() {
        this.sync();
    }

    sync() {
        const equalsRent = this.hasEqualsRentTarget && this.equalsRentTarget.checked;

        if (this.hasFixedAmountTarget) {
            this.fixedAmountTarget.readOnly = equalsRent;
            this.fixedAmountTarget.required = !equalsRent;
            if (equalsRent) {
                this.fixedAmountTarget.removeAttribute('min');
            } else {
                this.fixedAmountTarget.min = '0.01';
            }
        }

        if (this.hasFixedWrapTarget) {
            this.fixedWrapTarget.classList.toggle('is-disabled', equalsRent);
        }

        if (this.hasHintTarget) {
            this.hintTarget.textContent = equalsRent
                ? 'Mode actif : commission = loyer mensuel du contrat. Décochez la case pour utiliser une commission fixe.'
                : 'Mode actif : commission fixe. Saisissez le montant (ex. 100 $).';
        }
    }
}
