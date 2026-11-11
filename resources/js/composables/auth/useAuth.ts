import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { ComputedRef } from 'vue';
import type { Loaded, User } from '@/types';

/**
 * The signed-in member, as app/Http/Middleware/HandleInertiaRequests.php shares them with every page (null for a
 * guest), with whether anyone is signed in and whether they're staff.
 */
export function useAuth(): {
    user: ComputedRef<Loaded<User, 'email' | 'email_verified' | 'role'> | null>;
    signedIn: ComputedRef<boolean>;
    staff: ComputedRef<boolean>;
} {
    const page = usePage();
    const user = computed(() => page.props.auth.user);

    return {
        user,
        signedIn: computed(() => user.value !== null),
        staff: computed(() => user.value?.role.internal ?? false),
    };
}
