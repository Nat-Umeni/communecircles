import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import HeroPanel from '@/components/HeroPanel.vue';

describe('HeroPanel', () => {
    it('shows the heading, the text and the call-to-action buttons', () => {
        const wrapper = mount(HeroPanel, {
            props: { title: 'A place to learn and connect.', description: 'Join a Circle.' },
            slots: { default: '<button>Join the Circle</button>' },
        });

        expect(wrapper.get('h1').text()).toBe('A place to learn and connect.');
        expect(wrapper.get('p').text()).toBe('Join a Circle.');
        expect(wrapper.get('button').text()).toBe('Join the Circle');
        expect(wrapper.get('svg').attributes('aria-hidden')).toBe('true');
    });
});
