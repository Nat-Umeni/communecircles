import type { route as ziggyRoute } from 'ziggy-js';
import type { Option } from '@/types';

/**
 * Types $page.props and usePage().props for the props every page receives
 * from app/Http/Middleware/HandleInertiaRequests.php::share().
 */
declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            locale: string;
            locales: Option[];
        };
    }
}

/**
 * Ziggy's route() helper, added to every template by ZiggyVue in resources/js/app.ts.
 */
declare module 'vue' {
    interface ComponentCustomProperties {
        route: typeof ziggyRoute;
    }
}
