import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import MapPlaceholder from '@/components/ui/map/MapPlaceholder.vue';

describe('MapPlaceholder', () => {
    it('shows its message under a map pin, in the space a map would take', () => {
        const wrapper = mount(MapPlaceholder, { slots: { default: '<p>Not on the map yet.</p>' }, attrs: { class: 'min-h-64' } });

        expect(wrapper.text()).toBe('Not on the map yet.');
        expect(wrapper.get('svg').attributes('aria-hidden')).toBe('true');
        expect(wrapper.classes()).toContain('min-h-64');
    });
});
