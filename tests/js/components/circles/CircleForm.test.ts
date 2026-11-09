import { mount, type VueWrapper } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import CircleForm from '@/components/circles/CircleForm.vue';
import PlaceSearch from '@/components/circles/PlaceSearch.vue';
import type { CircleDetails, Option, Place } from '@/types';
import { fakeRouter, field, visit } from '@tests/support';

const countries: Option[] = [
    { value: 'GB', label: 'United Kingdom' },
    { value: 'LI', label: 'Liechtenstein' },
];

const totnes: Place = { geonames_id: 2635610, name: 'Totnes', region: 'England', country_code: 'GB', country_name: 'United Kingdom', latitude: 50.43107, longitude: -3.6843 };

const exchange: CircleDetails = {
    public_id: 'abc123',
    name: 'Seed Exchange',
    description: 'Swapping seeds that grow well here.',
    location: { name: 'Fernhill Farm', place: totnes, country_code: 'GB', country_name: 'United Kingdom' },
};

function mountNew(): VueWrapper {
    return mount(CircleForm, { props: { method: 'post', url: '/circles.store', countries, submitLabel: 'Start the circle' } });
}

function mountEdit(circle: CircleDetails): VueWrapper {
    return mount(CircleForm, { props: { method: 'put', url: '/circles.update/abc123', countries, circle, submitLabel: 'Save changes' } });
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
            { name: 'Seed Exchange', description: 'Swapping seeds that grow well here.', location_name: 'Fernhill Farm', country_code: '', geonames_id: 2635610 },
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
            { name: 'Seed Exchange', description: 'Swapping seeds that grow well here.', location_name: 'Fernhill Farm', country_code: 'LI', geonames_id: null },
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
            { name: 'Seed Swap', description: 'Swapping seeds that grow well here.', location_name: 'Fernhill Farm', country_code: 'GB', geonames_id: 2635610 },
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
            props: { method: 'put', url: '/circles.update/abc123', countries, circle: exchange, submitLabel: 'Save changes' },
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
