import './bootstrap';

// Eagerly load all images so they're included in the Vite manifest
import.meta.glob('../images/**/*', { eager: true });
