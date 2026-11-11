import type { route as ziggyRoute } from 'ziggy-js';
import type { Loaded, Option, User } from '@/types';

/**
 * Types $page.props and usePage().props for the props every page receives
 * from app/Http/Middleware/HandleInertiaRequests.php::share().
 */
declare module '@inertiajs/core' {
    export interface InertiaConfig {
        /** One-off messages from Inertia::flash(), e.g. "Your password has been reset." */
        flashDataType: {
            status?: string;
        };
        sharedPageProps: {
            auth: {
                /** UserResource for the member themselves, so with their email, confirmation and role. */
                user: Loaded<User, 'email' | 'email_verified' | 'role'> | null;
            };
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
