import { mount, type VueWrapper } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import CircleForm from '@/components/circles/CircleForm.vue';
import PlaceSearch from '@/components/circles/PlaceSearch.vue';
import MapView from '@/components/ui/map/MapView.vue';
import type { Circle, MapTiles, Option, Place } from '@/types';
import { fakeRouter, field, visit } from '@tests/support';

const countries: Option[] = [
    { value: 'GB', label: 'United Kingdom' },
    { value: 'LI', label: 'Liechtenstein' },
];

const totnes: Place = { geonames_id: 2635610, name: 'Totnes', region: 'England', country_code: 'GB', country_name: 'United Kingdom', latitude: 50.43107, longitude: -3.6843 };

const exchange: Circle = {
    public_id: 'abc123',
    name: 'Seed Exchange',
    description: 'Swapping seeds that grow well here.',
    location: { name: 'Fernhill Farm', place: totnes, country_code: 'GB', country_name: 'United Kingdom', coordinates: null, coordinates_hidden: false },
};

const tiles: MapTiles = {
    light: { url: 'https://tiles.example.com/{z}/{x}/{y}.png', attribution: '&copy; Example Maps', max_zoom: 17 },
    dark: { url: 'https://tiles.example.com/dark/{z}/{x}/{y}.png', attribution: '&copy; Example Maps', max_zoom: 17 },
};

/** Fernhill Farm's own exact spot, west of Totnes. */
const fernhill = { latitude: 50.4271, longitude: -3.7124 };

// NOTE: the map itself is MapView's to test; here it's what the form shows on it and does with a click.
const stubs = { MapView: true };

function mountNew(): VueWrapper {
    return mount(CircleForm, { props: { method: 'post', url: '/circles.store', countries, mapTiles: tiles, submitLabel: 'Start the circle' }, global: { stubs } });
}

function mountEdit(circle: Circle): VueWrapper {
    return mount(CircleForm, { props: { method: 'put', url: '/circles.update/abc123', countries, mapTiles: tiles, circle, submitLabel: 'Save changes' }, global: { stubs } });
}

/**
 * The map for picking the exact spot, typed as MapView so its props can be read.
 */
function spotMap(wrapper: VueWrapper) {
    return wrapper.getComponent(MapView);
}

async function choosePlace(wrapper: VueWrapper, place: Place | null): Promise<void> {
    wrapper.getComponent(PlaceSearch).vm.$emit('update:modelValue', place);
    await wrapper.vm.$nextTick();
}

describe('CircleForm', () => {
    it("sends a new circle's details, near the chosen town or village", async () => {
        const wrapper = mountNew();

        await field(wrapper, 'name').setValue('Seed Exchange');
        await field(wrapper, 'description').setValue('Swapping seeds that grow well here.');
        await choosePlace(wrapper, totnes);
        await field(wrapper, 'location_name').setValue('Fernhill Farm');
        await wrapper.get('form').trigger('submit');

        expect(fakeRouter.post).toHaveBeenCalledWith(
            '/circles.store',
            { name: 'Seed Exchange', description: 'Swapping seeds that grow well here.', location_name: 'Fernhill Farm', country_code: '', geonames_id: 2635610, latitude: '', longitude: '' },
            expect.anything(),
        );
    });

    it("sends a place GeoNames doesn't know by its name and country", async () => {
        const wrapper = mountNew();

        await field(wrapper, 'name').setValue('Seed Exchange');
        await field(wrapper, 'description').setValue('Swapping seeds that grow well here.');
        await field(wrapper, 'location_name').setValue('Fernhill Farm');
        await field(wrapper, 'country_code').setValue('LI');
        await wrapper.get('form').trigger('submit');

        expect(fakeRouter.post).toHaveBeenCalledWith(
            '/circles.store',
            { name: 'Seed Exchange', description: 'Swapping seeds that grow well here.', location_name: 'Fernhill Farm', country_code: 'LI', geonames_id: null, latitude: '', longitude: '' },
            expect.anything(),
        );
    });

    it('labels the country choice and lists the countries', () => {
        const wrapper = mountNew();
        const choices = field(wrapper, 'country_code').findAll('option');

        expect(choices.map((choice) => choice.text())).toEqual(['Choose a country', 'United Kingdom', 'Liechtenstein']);
        expect(wrapper.get(`label[for="${field(wrapper, 'country_code').attributes('id')}"]`).text()).toBe('Country');
    });

    it('fills in the circle being edited and sends the changes', async () => {
        const wrapper = mountEdit(exchange);

        expect(field(wrapper, 'name').element.value).toBe('Seed Exchange');
        expect(field(wrapper, 'description').element.value).toBe('Swapping seeds that grow well here.');
        expect(wrapper.getComponent(PlaceSearch).props('modelValue')).toEqual(totnes);
        expect(field(wrapper, 'location_name').element.value).toBe('Fernhill Farm');

        await field(wrapper, 'name').setValue('Seed Swap');
        await wrapper.get('form').trigger('submit');

        expect(fakeRouter.put).toHaveBeenCalledWith(
            '/circles.update/abc123',
            { name: 'Seed Swap', description: 'Swapping seeds that grow well here.', location_name: 'Fernhill Farm', country_code: 'GB', geonames_id: 2635610, latitude: '', longitude: '' },
            expect.anything(),
        );
    });

    it("fills in the name and country of an edited circle's place GeoNames doesn't know", () => {
        const wrapper = mountEdit({ ...exchange, location: { ...exchange.location!, place: null } });

        expect(wrapper.getComponent(PlaceSearch).props('modelValue')).toBeNull();
        expect(field(wrapper, 'location_name').element.value).toBe('Fernhill Farm');
        expect(field(wrapper, 'country_code').element.value).toBe('GB');
    });

    it('fills in an edited circle with no location', () => {
        const wrapper = mountEdit({ ...exchange, location: null });

        expect(wrapper.getComponent(PlaceSearch).props('modelValue')).toBeNull();
        expect(field(wrapper, 'location_name').element.value).toBe('');
        expect(field(wrapper, 'country_code').element.value).toBe('');
    });

    it("shows each field's error", async () => {
        const wrapper = mountNew();

        await wrapper.get('form').trigger('submit');
        await visit.fail(wrapper, {
            name: 'The name has already been taken.',
            description: 'The description field is required.',
            geonames_id: 'Choose a town or village from the list, or leave the location empty.',
            location_name: 'The place name field must not be greater than 255 characters.',
            country_code: 'Choose the country the place is in.',
        });

        expect(field(wrapper, 'name').attributes('aria-invalid')).toBe('true');
        expect(field(wrapper, 'description').attributes('aria-invalid')).toBe('true');
        expect(wrapper.get('[role="combobox"]').attributes('aria-invalid')).toBe('true');
        expect(field(wrapper, 'location_name').attributes('aria-invalid')).toBe('true');
        expect(field(wrapper, 'country_code').attributes('aria-invalid')).toBe('true');
        expect(wrapper.text()).toContain('The name has already been taken.');
        expect(wrapper.text()).toContain('The description field is required.');
        expect(wrapper.text()).toContain('Choose a town or village from the list, or leave the location empty.');
        expect(wrapper.text()).toContain('The place name field must not be greater than 255 characters.');
        expect(wrapper.text()).toContain('Choose the country the place is in.');
    });

    it('puts the actions it is given beside the button', () => {
        const wrapper = mount(CircleForm, {
            props: { method: 'put', url: '/circles.update/abc123', countries, mapTiles: tiles, circle: exchange, submitLabel: 'Save changes' },
            global: { stubs },
            slots: { actions: '<a href="/circles.show/abc123">Cancel</a>' },
        });

        const actions = wrapper.get('button[type="submit"]').element.parentElement!;
        expect(actions.querySelector('a')?.textContent).toBe('Cancel');
    });

    it("doesn't ask for the country while a town or village is chosen, since that says it", async () => {
        const wrapper = mountNew();

        await choosePlace(wrapper, totnes);
        expect(wrapper.find('[name="country_code"]').exists()).toBe(false);

        await choosePlace(wrapper, null);
        expect(wrapper.find('[name="country_code"]').exists()).toBe(true);

        expect(mountEdit(exchange).find('[name="country_code"]').exists()).toBe(false);
    });

    it('picks the exact spot where the map is clicked, to six decimal places, and sends it', async () => {
        const wrapper = mountNew();

        await field(wrapper, 'name').setValue('Seed Exchange');
        await field(wrapper, 'description').setValue('Swapping seeds that grow well here.');
        await field(wrapper, 'location_name').setValue('Fernhill Farm');
        await field(wrapper, 'country_code').setValue('GB');
        spotMap(wrapper).vm.$emit('pick', { latitude: 50.42712345, longitude: -3.71238765 });
        await wrapper.vm.$nextTick();

        expect([field(wrapper, 'latitude').element.value, field(wrapper, 'longitude').element.value]).toEqual(['50.427123', '-3.712388']);
        expect(spotMap(wrapper).props('markers')).toEqual([{ latitude: 50.427123, longitude: -3.712388, label: 'The exact spot' }]);

        await wrapper.get('form').trigger('submit');

        expect(fakeRouter.post).toHaveBeenCalledWith('/circles.store', expect.objectContaining({ latitude: '50.427123', longitude: '-3.712388' }), expect.anything());
    });

    it('moves the pin to a spot typed in, and only marks a whole spot on the globe', async () => {
        const wrapper = mountNew();

        await field(wrapper, 'latitude').setValue('50.4271');
        expect(spotMap(wrapper).props('markers')).toEqual([]);

        await field(wrapper, 'longitude').setValue('-3.7124');
        expect(spotMap(wrapper).props('markers')).toEqual([{ latitude: 50.4271, longitude: -3.7124, label: 'The exact spot' }]);

        await field(wrapper, 'latitude').setValue('95');
        expect(spotMap(wrapper).props('markers')).toEqual([]);
    });

    it("brings a typed spot into view once it's whole", async () => {
        const wrapper = mountNew();

        await field(wrapper, 'latitude').setValue('50.4271');
        await field(wrapper, 'longitude').setValue('-3.7124');
        await field(wrapper, 'longitude').trigger('change');

        expect([spotMap(wrapper).props('center'), spotMap(wrapper).props('zoom')]).toEqual([fernhill, 14]);
    });

    it('fills in and shows the exact spot of a circle being edited', () => {
        const wrapper = mountEdit({ ...exchange, location: { ...exchange.location!, coordinates: fernhill } });

        expect([field(wrapper, 'latitude').element.value, field(wrapper, 'longitude').element.value]).toEqual(['50.4271', '-3.7124']);
        expect([spotMap(wrapper).props('center'), spotMap(wrapper).props('zoom')]).toEqual([fernhill, 14]);
        expect(spotMap(wrapper).props('markers')).toEqual([{ ...fernhill, label: 'The exact spot' }]);
    });

    it('removes the exact spot', async () => {
        const wrapper = mountEdit({ ...exchange, location: { ...exchange.location!, coordinates: fernhill } });

        await wrapper.get('button[data-remove-spot]').trigger('click');

        expect([field(wrapper, 'latitude').element.value, field(wrapper, 'longitude').element.value]).toEqual(['', '']);
        expect(spotMap(wrapper).props('markers')).toEqual([]);
        expect(wrapper.find('button[data-remove-spot]').exists()).toBe(false);
    });

    it('starts the map on the town, or the whole world without one, and moves to a newly chosen town', async () => {
        expect([spotMap(mountEdit(exchange)).props('center'), spotMap(mountEdit(exchange)).props('zoom')]).toEqual([totnes, 13]);

        const wrapper = mountNew();
        expect([spotMap(wrapper).props('center'), spotMap(wrapper).props('zoom')]).toEqual([{ latitude: 20, longitude: 0 }, 2]);

        await choosePlace(wrapper, totnes);
        expect([spotMap(wrapper).props('center'), spotMap(wrapper).props('zoom')]).toEqual([totnes, 13]);
    });

    it("shows the map with the page's tiles, named for what it does, without zooming as the page scrolls", () => {
        expect(spotMap(mountNew()).props()).toEqual(expect.objectContaining({ label: 'Map to pick the exact spot', tiles, options: { scrollWheelZoom: false } }));
    });

    it("shows the exact spot's errors", async () => {
        const wrapper = mountNew();

        await wrapper.get('form').trigger('submit');
        await visit.fail(wrapper, {
            latitude: 'Give the place a name or choose its nearest town to go with its exact spot.',
            longitude: 'The longitude field must be between -180 and 180.',
        });

        expect(field(wrapper, 'latitude').attributes('aria-invalid')).toBe('true');
        expect(field(wrapper, 'longitude').attributes('aria-invalid')).toBe('true');
        expect(wrapper.text()).toContain('Give the place a name or choose its nearest town to go with its exact spot.');
        expect(wrapper.text()).toContain('The longitude field must be between -180 and 180.');
    });

    it('labels the button, which is disabled while sending', async () => {
        const wrapper = mountNew();
        const button = wrapper.get('button[type="submit"]');

        expect(button.text()).toBe('Start the circle');
        expect(button.attributes('disabled')).toBeUndefined();

        await wrapper.get('form').trigger('submit');
        await visit.start(wrapper);

        expect(button.attributes('disabled')).toBeDefined();
    });
});
