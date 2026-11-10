import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Badge from '@/components/ui/Badge.vue';
import CountPill from '@/components/ui/CountPill.vue';

function pill(count: number) {
    return mount(CountPill, { props: { count, singular: 'member', plural: 'members' } });
}

describe('CountPill', () => {
    it('counts things in a pill, in the plural', () => {
        const wrapper = pill(3);

        expect(wrapper.get('[data-count]').text()).toBe('3 members');
        expect(wrapper.getComponent(Badge).props('variant')).toBe('secondary');
        expect(pill(0).text()).toBe('0 members');
    });

    it('uses the singular for just one', () => {
        expect(pill(1).text()).toBe('1 member');
    });
});
