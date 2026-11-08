import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import CircleCard from '@/components/CircleCard.vue';

describe('CircleCard', () => {
    it("shows the circle's name with the accent dot, its description and a tag per channel", () => {
        const wrapper = mount(CircleCard, {
            props: { name: 'Permaculture & Farming', description: 'Growing food, soil, seeds and food systems.', channels: ['seed-saving', 'food-forests'] },
        });

        expect(wrapper.get('h3').text()).toBe('Permaculture & Farming');
        expect(wrapper.find('h3 span.bg-accent').exists()).toBe(true);
        expect(wrapper.get('header p').text()).toBe('Growing food, soil, seeds and food systems.');
        expect(wrapper.findAll('span.rounded-full.bg-secondary').map((tag) => tag.text())).toEqual(['#seed-saving', '#food-forests']);
    });
});
