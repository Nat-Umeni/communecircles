import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vitest';

// The theme lives in useTheme's module state, so each test loads a fresh copy of the toggle (and the composable).
async function freshThemeToggle() {
    vi.resetModules();

    return (await import('@/components/ThemeToggle.vue')).default;
}

afterEach(() => {
    document.documentElement.classList.remove('dark');
});

describe('ThemeToggle', () => {
    it('offers light, system and dark, starting on the system setting', async () => {
        const wrapper = mount(await freshThemeToggle());
        const buttons = wrapper.findAll('button');

        expect(wrapper.get('[role="group"]').attributes('aria-label')).toBe('Colour theme');
        expect(buttons.map((button) => button.attributes('title'))).toEqual(['Light theme', 'Match system theme', 'Dark theme']);
        expect(buttons[1].attributes('aria-pressed')).toBe('true');
    });

    it('switches and saves the theme when an option is clicked', async () => {
        const wrapper = mount(await freshThemeToggle());

        await wrapper.findAll('button')[2].trigger('click');

        expect(localStorage.getItem('theme')).toBe('dark');
        expect(document.documentElement.classList.contains('dark')).toBe(true);
        expect(wrapper.findAll('button')[2].attributes('aria-pressed')).toBe('true');
    });

    it('uses the light-on-green styling on the header bar', async () => {
        const wrapper = mount(await freshThemeToggle(), { props: { tone: 'brand' } });

        expect(wrapper.get('[role="group"]').classes()).toContain('bg-brand-foreground/10');
    });
});
