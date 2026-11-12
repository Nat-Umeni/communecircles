import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Edit from '@/pages/circles/Edit.vue';
import CircleForm from '@/components/circles/CircleForm.vue';
import type { Circle, MapTiles, Option } from '@/types';
import { fakeRouter, field, links } from '@tests/support';

const countries: Option[] = [{ value: 'GB', label: 'United Kingdom' }];

const tiles: MapTiles = {
    light: { url: 'https://tiles.example.com/{z}/{x}/{y}.png', attribution: '&copy; Example Maps', max_zoom: 17 },
    dark: { url: 'https://tiles.example.com/dark/{z}/{x}/{y}.png', attribution: '&copy; Example Maps', max_zoom: 17 },
};

const exchange: Circle = {
    public_id: 'abc123',
    name: 'Seed Exchange',
    description: 'Swapping seeds that grow well here.',
    location: { name: 'Fernhill Farm', place: null, country_code: 'GB', country_name: 'United Kingdom', coordinates: null, coordinates_hidden: false },
};

function edit() {
    return mount(Edit, { props: { circle: exchange, countries, map_tiles: tiles }, global: { stubs: { MapView: true } } });
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

    it('gives the form the map tiles for picking the exact spot', () => {
        expect(edit().getComponent(CircleForm).props('mapTiles')).toEqual(tiles);
    });

    it('goes back to the circle without saving', () => {
        expect(links(edit().get('form'))).toEqual([['Cancel', '/circles.show/abc123']]);
    });
});
