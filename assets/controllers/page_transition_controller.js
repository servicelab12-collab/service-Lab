import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['overlay'];

    connect() {
        this.onBefore = (event) => {
            const url = String(event.detail?.url ?? '');
            // PDF downloads never trigger turbo:load — don't lock the UI
            if (url.includes('/pdf')) {
                return;
            }
            this.show();
        };
        this.onAfter = () => {
            window.setTimeout(() => this.hide(), 120);
        };
        this.onError = () => this.hide();

        document.addEventListener('turbo:before-visit', this.onBefore);
        document.addEventListener('turbo:load', this.onAfter);
        document.addEventListener('turbo:render', this.onAfter);
        document.addEventListener('turbo:fetch-request-error', this.onError);
        document.addEventListener('turbo:visit', this.onVisitGuard = (event) => {
            // Safety: if a visit stalls, clear overlay on next interaction frame
            if (String(event.detail?.url ?? '').includes('/pdf')) {
                this.hide();
            }
        });
        this.hide();
        document.documentElement.classList.add('js-ready');
    }

    disconnect() {
        document.removeEventListener('turbo:before-visit', this.onBefore);
        document.removeEventListener('turbo:load', this.onAfter);
        document.removeEventListener('turbo:render', this.onAfter);
        document.removeEventListener('turbo:fetch-request-error', this.onError);
        document.removeEventListener('turbo:visit', this.onVisitGuard);
    }

    show() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        if (!this.hasOverlayTarget) return;
        this.overlayTarget.classList.add('is-active');
    }

    hide() {
        if (!this.hasOverlayTarget) return;
        this.overlayTarget.classList.remove('is-active');
    }
}
