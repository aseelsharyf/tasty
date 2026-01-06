import './bootstrap';
import { decode } from 'blurhash';

// Eagerly load all images so they're included in the Vite manifest
import.meta.glob('../images/**/*', { eager: true });

// Initialize BlurHash placeholders
function initBlurHash() {
    const elements = document.querySelectorAll('[data-blurhash]');

    elements.forEach((element) => {
        const blurhash = element.getAttribute('data-blurhash');
        const canvasId = element.getAttribute('data-blurhash-id');

        if (!blurhash || !canvasId) return;

        const canvas = document.getElementById(canvasId);
        if (!canvas || !(canvas instanceof HTMLCanvasElement)) return;

        try {
            const pixels = decode(blurhash, 32, 32);
            const ctx = canvas.getContext('2d');
            if (!ctx) return;

            const imageData = ctx.createImageData(32, 32);
            imageData.data.set(pixels);
            ctx.putImageData(imageData, 0, 0);
        } catch (e) {
            console.warn('Failed to decode blurhash:', e);
        }
    });
}

// Run on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBlurHash);
} else {
    initBlurHash();
}
