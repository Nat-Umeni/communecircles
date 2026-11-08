import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import AppLogo from '@/components/AppLogo.vue';

describe('AppLogo', () => {
    it('links to the given page with the logo mark and the site name', () => {
        const wrapper = mount(AppLogo, { props: { href: '/home' } });

        expect(wrapper.element.tagName).toBe('A');
        expect(wrapper.attributes('href')).toBe('/home');
        expect(wrapper.get('img').attributes('src')).toBe('/images/logo-mark.png');
        expect(wrapper.text()).toBe('Commune Circles');
    });
});
