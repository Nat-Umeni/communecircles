import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Edit from '@/pages/circles/Edit.vue';
import type { CircleDetails, Option } from '@/types';
import { fakeRouter, field, links } from '@tests/support';

const countries: Option[] = [{ value: 'GB', label: 'United Kingdom' }];

const exchange: CircleDetails = {
    public_id: 'abc123',
    name: 'Seed Exchange',
    description: 'Swapping seeds that grow well here.',
    location: { name: 'Fernhill Farm', place: null, country_code: 'GB', country_name: 'United Kingdom' },
};

function edit() {
    return mount(Edit, { props: { circle: exchange, countries } });
}

describe('circles/Edit', () => {
    it('fills in the circle and saves the changes to it', async () => {
        const wrapper = edit();

        expect(field(wrapper, 'name').element.value).toBe('Seed Exchange');
        expect(field(wrapper, 'country_code').findAll('option').map((option) => option.text())).toContain('United Kingdom');
        expect(wrapper.get('button[type="submit"]').text()).toBe('Save changes');

        await field(wrapper, 'name').setValue('Seed Swap');
        await wrapper.get('form').trigger('submit');

        expect(fakeRouter.put).toHaveBeenCalledWith('/circles.update/abc123', expect.objectContaining({ name: 'Seed Swap' }), expect.anything());
    });

    it('goes back to the circle without saving', () => {
        expect(links(edit().get('form'))).toEqual([['Cancel', '/circles.show/abc123']]);
    });
});
