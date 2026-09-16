import { Controller } from '@hotwired/stimulus';

/**
 * Animated mesh orbs + subtle particle field for the auth showcase.
 */
export default class extends Controller {
    static targets = ['orb', 'canvas'];

    connect() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        this.raf = null;
        this.particles = [];
        this.pointer = { x: 0.5, y: 0.5 };

        this.onPointer = (event) => {
            const rect = this.element.getBoundingClientRect();
            this.pointer.x = (event.clientX - rect.left) / Math.max(rect.width, 1);
            this.pointer.y = (event.clientY - rect.top) / Math.max(rect.height, 1);
        };

        this.element.addEventListener('pointermove', this.onPointer);

        if (this.hasCanvasTarget) {
            this.setupParticles();
            this.draw = this.draw.bind(this);
            this.raf = requestAnimationFrame(this.draw);
        }

        this.orbTargets.forEach((orb, index) => {
            orb.style.setProperty('--orb-delay', `${index * 1.2}s`);
        });
    }

    disconnect() {
        this.element.removeEventListener('pointermove', this.onPointer);
        if (this.raf) {
            cancelAnimationFrame(this.raf);
        }
    }

    setupParticles() {
        const canvas = this.canvasTarget;
        const parent = canvas.parentElement;
        const dpr = Math.min(window.devicePixelRatio || 1, 2);
        const width = parent.clientWidth || 800;
        const height = parent.clientHeight || 900;

        canvas.width = width * dpr;
        canvas.height = height * dpr;
        canvas.style.width = `${width}px`;
        canvas.style.height = `${height}px`;

        this.ctx = canvas.getContext('2d');
        this.ctx.scale(dpr, dpr);
        this.size = { width, height };

        const count = Math.min(48, Math.floor((width * height) / 18000));
        this.particles = Array.from({ length: count }, () => ({
            x: Math.random() * width,
            y: Math.random() * height,
            r: 1 + Math.random() * 1.8,
            vx: -0.25 + Math.random() * 0.5,
            vy: -0.35 + Math.random() * 0.2,
            a: 0.15 + Math.random() * 0.35,
        }));
    }

    draw() {
        if (!this.ctx) return;

        const { width, height } = this.size;
        const ctx = this.ctx;
        ctx.clearRect(0, 0, width, height);

        // Soft parallax drift for orbs (keeps CSS float animation)
        this.orbTargets.forEach((orb, index) => {
            const depth = (index + 1) * 10;
            const x = (this.pointer.x - 0.5) * depth;
            const y = (this.pointer.y - 0.5) * depth;
            orb.style.setProperty('--parallax-x', `${x}px`);
            orb.style.setProperty('--parallax-y', `${y}px`);
        });

        for (const p of this.particles) {
            p.x += p.vx;
            p.y += p.vy;
            if (p.x < -10) p.x = width + 10;
            if (p.x > width + 10) p.x = -10;
            if (p.y < -10) p.y = height + 10;
            if (p.y > height + 10) p.y = -10;

            ctx.beginPath();
            ctx.fillStyle = `rgba(150, 180, 255, ${p.a})`;
            ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
            ctx.fill();
        }

        // Connecting lines near pointer
        const px = this.pointer.x * width;
        const py = this.pointer.y * height;
        for (let i = 0; i < this.particles.length; i++) {
            const a = this.particles[i];
            const dx = a.x - px;
            const dy = a.y - py;
            const dist = Math.hypot(dx, dy);
            if (dist < 120) {
                ctx.strokeStyle = `rgba(100, 140, 255, ${0.18 * (1 - dist / 120)})`;
                ctx.beginPath();
                ctx.moveTo(a.x, a.y);
                ctx.lineTo(px, py);
                ctx.stroke();
            }
        }

        this.raf = requestAnimationFrame(this.draw);
    }
}
