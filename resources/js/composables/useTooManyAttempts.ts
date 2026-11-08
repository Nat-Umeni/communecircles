import type { HttpExceptionResponse } from '@inertiajs/core';

/**
 * An onHttpException handler for forms behind Laravel's throttle middleware: a 429 becomes a message
 * on the form (via showError) instead of Inertia's error dialog. Any other status is left to Inertia.
 */
export function useTooManyAttempts(showError: (message: string) => void): (response: HttpExceptionResponse) => boolean | void {
    return (response) => {
        if (response.status !== 429) {
            return;
        }

        const seconds = Number(response.headers['retry-after']);
        showError(seconds > 0 ? `Too many attempts. Please try again in ${seconds} seconds.` : 'Too many attempts. Please try again in a minute.');

        return false;
    };
}
