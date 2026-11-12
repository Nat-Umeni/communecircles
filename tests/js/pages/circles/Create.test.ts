import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Create from '@/pages/circles/Create.vue';
import CircleForm from '@/components/circles/CircleForm.vue';
import type { MapTiles, Option } from '@/types';
import { fakeRouter, field } from '@tests/support';

const countries: Option[] = [{ value: 'GB', label: 'United Kingdom' }];

const tiles: MapTiles = {
    light: { url: 'https://tiles.example.com/{z}/{x}/{y}.png', attribution: '&copy; Example Maps', max_zoom: 17 },
    dark: { url: 'https://tiles.example.com/dark/{z}/{x}/{y}.png', attribution: '&copy; Example Maps', max_zoom: 17 },
};

function create(cannotCreateReason: string | null = null) {
    return mount(Create, { props: { cannot_create_reason: cannotCreateReason, countries, map_tiles: tiles }, global: { stubs: { MapView: true } } });
}

describe('circles/Create', () => {
    it('sends a new circle to be checked', async () => {
        const wrapper = create();

        expect(wrapper.text()).toContain('The team checks each new circle before it is listed.');
        expect(field(wrapper, 'country_code').findAll('option').map((option) => option.text())).toContain('United Kingdom');
        expect(wrapper.get('button[type="submit"]').text()).toBe('Start the circle');

        await field(wrapper, 'name').setValue('Seed Exchange');
        await wrapper.get('form').trigger('submit');

        expect(fakeRouter.post).toHaveBeenCalledWith('/circles.store', expect.objectContaining({ name: 'Seed Exchange' }), expect.anything());
    });

    it('gives the form the map tiles for picking the exact spot', () => {
        expect(create().getComponent(CircleForm).props('mapTiles')).toEqual(tiles);
    });

    it("says why the member can't start a circle yet, instead of the form", () => {
        const wrapper = create('New accounts can create a circle 5 minutes after joining.');

        expect(wrapper.get('[role="status"]').text()).toBe('New accounts can create a circle 5 minutes after joining.');
        expect(wrapper.find('form').exists()).toBe(false);
    });
});
