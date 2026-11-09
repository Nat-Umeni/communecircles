import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import CircleLocation from '@/components/circles/CircleLocation.vue';
import type { Location, Place } from '@/types';

const totnes: Place = { geonames_id: 2635610, name: 'Totnes', region: 'England', country_code: 'GB', country_name: 'United Kingdom', latitude: 50.43107, longitude: -3.6843 };

function location(overrides: Partial<Location>): Location {
    return { name: null, place: totnes, country_code: 'GB', country_name: 'United Kingdom', ...overrides };
}

describe('CircleLocation', () => {
    it("says where a circle with a name of its own is, near the GeoNames place", () => {
        const wrapper = mount(CircleLocation, { props: { location: location({ name: 'Fernhill Farm' }) } });

        expect(wrapper.text()).toBe('Fernhill Farm, near Totnes, England, United Kingdom');
        expect(wrapper.get('svg').attributes('aria-hidden')).toBe('true');
    });

    it('says which town or village a circle is in', () => {
        expect(mount(CircleLocation, { props: { location: location({}) } }).text()).toBe('Totnes, England, United Kingdom');
    });

    it('leaves out the region when GeoNames has none', () => {
        const vaduz = { ...totnes, name: 'Vaduz', region: null, country_code: 'LI', country_name: 'Liechtenstein' };

        expect(mount(CircleLocation, { props: { location: location({ place: vaduz, country_code: 'LI', country_name: 'Liechtenstein' }) } }).text())
            .toBe('Vaduz, Liechtenstein');
    });

    it('says the name and country of a place GeoNames does not know', () => {
        expect(mount(CircleLocation, { props: { location: location({ name: 'Fernhill Farm', place: null }) } }).text()).toBe('Fernhill Farm, United Kingdom');
    });
});
