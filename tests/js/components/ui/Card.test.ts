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

    it('links the title when given an address', () => {
        const wrapper = mount(Card, { props: { title: 'Exchange', href: '/circles.show/k3x9q2mzt8' } });

        expect(wrapper.get('h3 a').text()).toBe('Exchange');
        expect(wrapper.get('h3 a').attributes('href')).toBe('/circles.show/k3x9q2mzt8');
    });

    it("puts the aside at the end of the title's row, with the description below", () => {
        const wrapper = mount(Card, { props: { title: 'Members', description: 'Everyone in the circle.' }, slots: { aside: '<form role="search"></form>' } });
        const row = wrapper.get('h3').element.parentElement!;

        expect(wrapper.get('form[role="search"]').element.parentElement).toBe(row);
        expect(row.classList).toContain('justify-between');
        expect(row.nextElementSibling).toBe(wrapper.get('header p').element);
    });

    it('shows the header for just an aside', () => {
        const wrapper = mount(Card, { slots: { aside: '<form role="search"></form>' } });

        expect(wrapper.get('header').find('form[role="search"]').exists()).toBe(true);
        expect(wrapper.find('h3').exists()).toBe(false);
    });

    it('leaves out the header without a title or description', () => {
        const wrapper = mount(Card, { slots: { default: 'Just content' } });

        expect(wrapper.find('header').exists()).toBe(false);
        expect(wrapper.text()).toBe('Just content');
    });

    it('can show just a description', () => {
        const wrapper = mount(Card, { props: { description: 'New posts from every circle will show up here.' } });

        expect(wrapper.find('h3').exists()).toBe(false);
        expect(wrapper.get('header').element.children).toHaveLength(1);
        expect(wrapper.get('header p').text()).toBe('New posts from every circle will show up here.');
    });
});
