import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Card from '@/components/ui/Card.vue';

describe('Card', () => {
    it('shows a title and description above its content', () => {
        const wrapper = mount(Card, { props: { title: 'Commune Circles', description: 'About the site itself.' }, slots: { default: '<p>Body</p>' } });

        expect(wrapper.get('h3').text()).toBe('Commune Circles');
        expect(wrapper.get('header p').text()).toBe('About the site itself.');
        expect(wrapper.html()).toContain('<p>Body</p>');
    });

    it('shows the accent dot before the title when marked', () => {
        const marked = mount(Card, { props: { title: 'Exchange', marker: true } });
        const plain = mount(Card, { props: { title: 'Exchange' } });

        expect(marked.find('h3 span.bg-accent').exists()).toBe(true);
        expect(plain.find('h3 span.bg-accent').exists()).toBe(false);
    });

    it('leaves out the header without a title or description', () => {
        const wrapper = mount(Card, { slots: { default: 'Just content' } });

        expect(wrapper.find('header').exists()).toBe(false);
        expect(wrapper.text()).toBe('Just content');
    });

    it('can show just a description', () => {
        const wrapper = mount(Card, { props: { description: 'New posts from every circle will show up here.' } });

        expect(wrapper.find('h3').exists()).toBe(false);
        expect(wrapper.get('header p').text()).toBe('New posts from every circle will show up here.');
    });
});
