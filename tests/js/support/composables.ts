import { mount, type VueWrapper } from '@vue/test-utils';
import { defineComponent, h } from 'vue';

/**
 * Run a composable inside a component, for composables that need one (lifecycle hooks, useForm). Unmount the
 * wrapper to see what the composable does once its component is gone.
 */
export function withSetup<T>(composable: () => T): { result: T; wrapper: VueWrapper } {
    let result!: T;
    const wrapper = mount(
        defineComponent({
            setup() {
                result = composable();

                return () => h('div');
            },
        }),
    );

    return { result, wrapper };
}
