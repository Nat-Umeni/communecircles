import '../css/app.css';
import { createInertiaApp } from '@inertiajs/vue3';

createInertiaApp({
    title: (title) => (title ? `${title} · Commune Circles` : 'Commune Circles'),
    progress: {
        color: 'var(--accent)',
    },
});
