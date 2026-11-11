import { describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';
import { useFormDialog } from '@/composables/ui/useFormDialog';
import { fakeRouter, lastPostOptions, visit, withSetup } from '@tests/support';

describe('useFormDialog', () => {
    it('starts closed, with the form', () => {
        const { result } = withSetup(() => useFormDialog({ reason: '' }));

        expect(result.open.value).toBe(false);
        expect(result.form.reason).toBe('');
    });

    it('opens and closes through an open state it is given, e.g. a v-model', () => {
        const open = ref(true);
        const { result } = withSetup(() => useFormDialog({}, open));

        expect(result.open).toBe(open);
    });

    it('sends the form with the method, address and options given', () => {
        const { result } = withSetup(() => useFormDialog({ reason: '' }));

        result.form.reason = 'Spam';
        result.send('post', '/approvals.reject/5', { preserveScroll: true });

        expect(fakeRouter.post).toHaveBeenCalledWith('/approvals.reject/5', { reason: 'Spam' }, expect.objectContaining({ preserveScroll: true }));
    });

    it('can send with other methods too', () => {
        const { result } = withSetup(() => useFormDialog({}));

        result.send('delete', '/circles.destroy/abc123');

        expect(fakeRouter.delete).toHaveBeenCalledWith('/circles.destroy/abc123', expect.anything());
    });

    it('closes and resets once sent, then runs the onSuccess it was given', () => {
        const onSuccess = vi.fn();
        const { result } = withSetup(() => useFormDialog({ reason: '' }));

        result.open.value = true;
        result.form.reason = 'Spam';
        result.send('post', '/approvals.reject/5', { onSuccess });
        lastPostOptions().onSuccess({} as never);

        expect(result.open.value).toBe(false);
        expect(result.form.reason).toBe('');
        expect(onSuccess).toHaveBeenCalledOnce();
    });

    it('stays open with what was entered when sending fails', async () => {
        const { result, wrapper } = withSetup(() => useFormDialog({ reason: '' }));

        result.open.value = true;
        result.form.reason = 'Spam';
        result.send('post', '/approvals.reject/5');
        await visit.fail(wrapper, { reason: 'The reason may not be longer than 500 characters.' });

        expect(result.open.value).toBe(true);
        expect(result.form.reason).toBe('Spam');
        expect(result.form.errors.reason).toBe('The reason may not be longer than 500 characters.');
    });
});
