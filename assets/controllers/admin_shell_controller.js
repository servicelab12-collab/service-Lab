import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['sidebar', 'backdrop'];

    toggle() {
        const open = !this.sidebarTarget.classList.contains('is-open');
        this.sidebarTarget.classList.toggle('is-open', open);
        this.element.classList.toggle('is-sidebar-open', open);
        if (this.hasBackdropTarget) {
            this.backdropTarget.classList.toggle('is-visible', open);
        }
        document.body.classList.toggle('admin-nav-locked', open);
    }

    close() {
        this.sidebarTarget.classList.remove('is-open');
        this.element.classList.remove('is-sidebar-open');
        if (this.hasBackdropTarget) {
            this.backdropTarget.classList.remove('is-visible');
        }
        document.body.classList.remove('admin-nav-locked');
    }
}
