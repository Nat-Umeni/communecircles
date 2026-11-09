import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import CircleCard from '@/components/circles/CircleCard.vue';
import type { CircleSummary } from '@/types';

const exchange: CircleSummary = {
    public_id: 'k3x9q2mzt8',
    name: 'Exchange',
    description: 'Share skills and lend tools.',
    members_count: 3,
    location: null,
};

describe('CircleCard', () => {
    it("shows the circle's name linking to its page, with the accent dot and its description", () => {
        const wrapper = mount(CircleCard, { props: { circle: exchange } });

        expect(wrapper.get('h3 a').text()).toBe('Exchange');
        expect(wrapper.get('h3 a').attributes('href')).toBe('/circles.show/k3x9q2mzt8');
        expect(wrapper.find('h3 span.bg-accent').exists()).toBe(true);
        expect(wrapper.get('header p').text()).toBe('Share skills and lend tools.');
    });

    it('says how many members it has', () => {
        expect(mount(CircleCard, { props: { circle: exchange } }).text()).toContain('3 members');
        expect(mount(CircleCard, { props: { circle: { ...exchange, members_count: 1 } } }).text()).toContain('1 member');
        expect(mount(CircleCard, { props: { circle: { ...exchange, members_count: 1 } } }).text()).not.toContain('1 members');
    });
});
