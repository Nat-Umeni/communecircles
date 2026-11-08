import type { Option } from '@/types';

/**
 * Types $page.props and usePage().props for the props every page receives
 * from app/Http/Middleware/HandleInertiaRequests.php::share().
 */
declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            homeUrl: string;
            locale: string;
            locales: Option[];
        };
    }
}
