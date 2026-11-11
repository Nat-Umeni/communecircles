import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import CircleCard from '@/components/circles/CircleCard.vue';
import CountPill from '@/components/ui/CountPill.vue';
import type { Circle, Loaded } from '@/types';

const exchange: Loaded<Circle, 'members_count'> = {
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

    it('says how many members it has in the member-count pill', () => {
        expect(mount(CircleCard, { props: { circle: exchange } }).getComponent(CountPill).props()).toEqual({ count: 3, singular: 'member', plural: 'members' });
    });

    it('says where the circle is when it has a location', () => {
        const located: Loaded<Circle, 'members_count'> = {
            ...exchange,
            location: { name: 'Fernhill Farm', place: null, country_code: 'GB', country_name: 'United Kingdom' },
        };

        expect(mount(CircleCard, { props: { circle: located } }).text()).toContain('Fernhill Farm, United Kingdom');
        expect(mount(CircleCard, { props: { circle: exchange } }).find('svg').exists()).toBe(false);
    });

    it("shows a badge when one of the member's own circles is not approved yet", () => {
        const waiting: Loaded<Circle, 'members_count' | 'status'> = { ...exchange, status: { value: 'pending', label: 'Waiting for approval' } };
        const approved: Loaded<Circle, 'members_count' | 'status'> = { ...exchange, status: { value: 'approved', label: 'Approved' } };

        const rejected: Loaded<Circle, 'members_count' | 'status'> = { ...exchange, status: { value: 'rejected', label: 'Not approved' } };

        expect(mount(CircleCard, { props: { circle: waiting } }).get('[data-status]').text()).toBe('Waiting for approval');
        expect(mount(CircleCard, { props: { circle: waiting } }).get('[data-status]').classes()).toContain('bg-warning');
        expect(mount(CircleCard, { props: { circle: rejected } }).get('[data-status]').text()).toBe('Not approved');
        expect(mount(CircleCard, { props: { circle: rejected } }).get('[data-status]').classes()).toContain('bg-danger');
        expect(mount(CircleCard, { props: { circle: approved } }).find('[data-status]').exists()).toBe(false);
        expect(mount(CircleCard, { props: { circle: exchange } }).find('[data-status]').exists()).toBe(false);
    });
});
