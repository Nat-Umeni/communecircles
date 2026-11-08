import type { VueWrapper } from '@vue/test-utils';
import { lastPostOptions } from './router';

/**
 * Plays out the stages Inertia would run for the last useForm().post(), so a test can see the page react.
 */
export const visit = {
    async start(wrapper: VueWrapper): Promise<void> {
        lastPostOptions().onStart({} as never);
        await wrapper.vm.$nextTick();
    },
    async finish(wrapper: VueWrapper): Promise<void> {
        lastPostOptions().onFinish({} as never);
        await wrapper.vm.$nextTick();
    },
    async fail(wrapper: VueWrapper, errors: Record<string, string>): Promise<void> {
        lastPostOptions().onError(errors as never);
        await wrapper.vm.$nextTick();
    },
    async throttle(wrapper: VueWrapper, retryAfterSeconds: number): Promise<void> {
        lastPostOptions().onHttpException({ status: 429, headers: { 'retry-after': String(retryAfterSeconds) }, data: '' } as never);
        await wrapper.vm.$nextTick();
    },
};
