import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['button', 'menu'];

    connect() {
        this.onScroll = () => this.syncSolid();
        window.addEventListener('scroll', this.onScroll, { passive: true });
        this.syncSolid();
    }

    disconnect() {
        window.removeEventListener('scroll', this.onScroll);
    }

    syncSolid() {
        const solid = window.scrollY > 24 || this.element.classList.contains('is-nav-open');
        this.element.classList.toggle('is-solid', solid);
    }

    toggle() {
        const open = !this.element.classList.contains('is-nav-open');
        this.element.classList.toggle('is-nav-open', open);
        if (this.hasButtonTarget) {
            this.buttonTarget.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
        this.syncSolid();
    }
}
