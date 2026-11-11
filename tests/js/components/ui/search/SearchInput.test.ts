import { mount, type VueWrapper } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vitest';
import SearchInput from '@/components/ui/search/SearchInput.vue';

/**
 * A SearchInput bound with v-model, as a page uses it.
 */
function searchInput(text = ''): VueWrapper {
    const wrapper: VueWrapper = mount(SearchInput, {
        props: { modelValue: text, 'onUpdate:modelValue': (value: string) => wrapper.setProps({ modelValue: value }) },
        attrs: { name: 'search', 'aria-label': 'Search circles', placeholder: 'Search circles by name' },
        attachTo: document.body,
    });

    return wrapper;
}

describe('SearchInput', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('is a search box, with what it searches on the box itself', () => {
        const input = searchInput().get('input');

        expect(input.attributes('type')).toBe('search');
        expect(input.attributes('name')).toBe('search');
        expect(input.attributes('aria-label')).toBe('Search circles');
        expect(input.attributes('placeholder')).toBe('Search circles by name');
    });

    it('updates as you type', async () => {
        const wrapper = searchInput();

        await wrapper.get('input').setValue('seed');

        expect(wrapper.emitted('update:modelValue')).toEqual([['seed']]);
    });

    it('offers to clear the search only when there is something in it', () => {
        expect(searchInput().find('button').exists()).toBe(false);
        expect(searchInput('seed').get('button').attributes('aria-label')).toBe('Clear the search');
    });

    it('clears the search, puts you back in the box, and says so', async () => {
        const wrapper = searchInput('seed');

        await wrapper.get('button').trigger('click');

        expect(wrapper.emitted('update:modelValue')).toEqual([['']]);
        expect(wrapper.emitted('clear')).toHaveLength(1);
        expect(document.activeElement).toBe(wrapper.get('input').element);
        expect(wrapper.find('button').exists()).toBe(false);
    });
});
