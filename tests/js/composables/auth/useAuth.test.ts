import { describe, expect, it } from 'vitest';
import { useAuth } from '@/composables/auth/useAuth';
import { signIn } from '@tests/support';

describe('useAuth', () => {
    it('has no one signed in for a guest', () => {
        const { user, signedIn, staff } = useAuth();

        expect(user.value).toBeNull();
        expect(signedIn.value).toBe(false);
        expect(staff.value).toBe(false);
    });

    it('is the signed-in member, who is not staff', () => {
        const member = signIn();
        const { user, signedIn, staff } = useAuth();

        expect(user.value).toEqual(member);
        expect(signedIn.value).toBe(true);
        expect(staff.value).toBe(false);
    });

    it('knows when the member is staff', () => {
        signIn({ role: { value: 'admin', label: 'Admin', internal: true } });

        expect(useAuth().staff.value).toBe(true);
    });

    it('follows someone signing in after it was set up', () => {
        const { signedIn } = useAuth();

        signIn();

        expect(signedIn.value).toBe(true);
    });
});
