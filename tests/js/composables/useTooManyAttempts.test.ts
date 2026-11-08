import { describe, expect, it, vi } from 'vitest';
import { useTooManyAttempts } from '@/composables/useTooManyAttempts';

const response = (status: number, headers: Record<string, string> = {}) => ({ status, headers, data: '' });

describe('useTooManyAttempts', () => {
    it('shows how long to wait when the throttle answers 429, and stops Inertia showing its error dialog', () => {
        const showError = vi.fn();

        const result = useTooManyAttempts(showError)(response(429, { 'retry-after': '42' }));

        expect(showError).toHaveBeenCalledWith('Too many attempts. Please try again in 42 seconds.');
        expect(result).toBe(false);
    });

    it('falls back to "a minute" without a Retry-After header', () => {
        const showError = vi.fn();

        useTooManyAttempts(showError)(response(429));

        expect(showError).toHaveBeenCalledWith('Too many attempts. Please try again in a minute.');
    });

    it('leaves any other error to Inertia', () => {
        const showError = vi.fn();

        const result = useTooManyAttempts(showError)(response(500));

        expect(showError).not.toHaveBeenCalled();
        expect(result).toBeUndefined();
    });
});
