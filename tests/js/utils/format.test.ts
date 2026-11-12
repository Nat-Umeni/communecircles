import { describe, expect, it } from 'vitest';
import type { Location, Place } from '@/types';
import { fullName, locationLabel, placeLabel } from '@/utils/format';

const totnes: Place = { geonames_id: 2635610, name: 'Totnes', region: 'England', country_code: 'GB', country_name: 'United Kingdom', latitude: 50.43107, longitude: -3.6843 };

function location(overrides: Partial<Location>): Location {
    return { name: null, place: totnes, country_code: 'GB', country_name: 'United Kingdom', coordinates: null, coordinates_hidden: false, ...overrides };
}

describe('fullName', () => {
    it('puts the first name before the last', () => {
        expect(fullName({ first_name: 'Grace', last_name: 'Hopper' })).toBe('Grace Hopper');
    });
});

describe('placeLabel', () => {
    it('names the town, its region and its country', () => {
        expect(placeLabel({ name: 'Totnes', region: 'England', country_name: 'United Kingdom' })).toBe('Totnes, England, United Kingdom');
    });

    it('leaves out a region GeoNames does not have', () => {
        expect(placeLabel({ name: 'Vaduz', region: null, country_name: 'Liechtenstein' })).toBe('Vaduz, Liechtenstein');
    });
});

describe('locationLabel', () => {
    it('says where somewhere with a name of its own is, near the GeoNames place', () => {
        expect(locationLabel(location({ name: 'Fernhill Farm' }))).toBe('Fernhill Farm, near Totnes, England, United Kingdom');
    });

    it('names the town or village when that is all there is', () => {
        expect(locationLabel(location({}))).toBe('Totnes, England, United Kingdom');
    });

    it('says the name and country of a place GeoNames does not know', () => {
        expect(locationLabel(location({ name: 'Fernhill Farm', place: null }))).toBe('Fernhill Farm, United Kingdom');
    });
});
