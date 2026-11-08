import '../css/app.css';
import { createInertiaApp } from '@inertiajs/vue3';
import { ZiggyVue } from 'ziggy-js';

createInertiaApp({
    title: (title) => (title ? `${title} · Commune Circles` : 'Commune Circles'),
    progress: {
        color: 'var(--accent)',
    },
    // route() in every template, using the routes from @routes in resources/views/app.blade.php.
    withApp: (app) => {
        app.use(ZiggyVue);
    },
});
