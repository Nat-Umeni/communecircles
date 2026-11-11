import { describe, expect, it } from 'vitest';
import { fullName, placeLabel } from '@/utils/format';

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
