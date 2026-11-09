import { mount, type VueWrapper } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vitest';
import AppSidebar from '@/components/AppSidebar.vue';
import { links, page, signIn } from '@tests/support';

let wrapper: VueWrapper | undefined;

function sidebar(open: boolean) {
    wrapper = mount(AppSidebar, { props: { open } });

    return wrapper;
}

const pressKey = (key: string) => window.dispatchEvent(new KeyboardEvent('keydown', { key }));

afterEach(() => {
    wrapper?.unmount();
    wrapper = undefined;
});

describe('AppSidebar', () => {
    it('lists the sections, with Circles linking home and marked current on the circle list', () => {
        page.component = 'circles/Index';
        const wrapper = sidebar(false);
        const circles = wrapper.get('nav[aria-label="Sections"] a');

        expect(wrapper.findAll('nav[aria-label="Sections"] > *').map((item) => item.text())).toEqual(['Circles', 'Explore', 'Learn', 'Exchange', 'Skills', 'Community', 'Resources']);
        expect(circles.attributes('href')).toBe('/home');
        expect(circles.attributes('aria-current')).toBe('page');
    });

    it('does not mark Circles current on other pages', () => {
        page.component = 'Styleguide';

        expect(sidebar(false).get('nav[aria-label="Sections"] a').attributes('aria-current')).toBeUndefined();
    });

    it('gives staff a link to the approvals queue, marked current there', () => {
        signIn({ role: { value: 'admin', label: 'Admin', internal: true } });
        page.component = 'approvals/Index';
        const staff = sidebar(false).get('nav[aria-label="Staff"]');

        expect(links(staff)).toEqual([['Approvals', '/approvals.index']]);
        expect(staff.get('a').attributes('aria-current')).toBe('page');
        expect(staff.get('a svg').attributes('aria-hidden')).toBe('true');
    });

    it("doesn't mark Approvals current on other pages", () => {
        signIn({ role: { value: 'admin', label: 'Admin', internal: true } });
        page.component = 'circles/Index';

        expect(sidebar(false).get('nav[aria-label="Staff"] a').attributes('aria-current')).toBeUndefined();
    });

    it("doesn't show members or guests the staff links", () => {
        signIn();
        expect(sidebar(false).find('nav[aria-label="Staff"]').exists()).toBe(false);
        wrapper!.unmount();

        page.props.auth.user = null;
        expect(sidebar(false).find('nav[aria-label="Staff"]').exists()).toBe(false);
    });

    it('is hidden off-screen on phones with no overlay until opened', () => {
        const wrapper = sidebar(false);

        expect(wrapper.get('aside').classes()).toContain('-translate-x-full');
        expect(wrapper.find('[aria-hidden="true"].fixed').exists()).toBe(false);
    });

    it('slides in with an overlay that closes it when clicked', async () => {
        const wrapper = sidebar(true);

        expect(wrapper.get('aside').classes()).toContain('translate-x-0');

        await wrapper.get('[aria-hidden="true"].fixed').trigger('click');

        expect(wrapper.emitted('update:open')).toEqual([[false]]);
    });

    it('closes on Escape, ignoring other keys', () => {
        const wrapper = sidebar(true);

        pressKey('Enter');
        expect(wrapper.emitted('update:open')).toBeUndefined();

        pressKey('Escape');
        expect(wrapper.emitted('update:open')).toEqual([[false]]);
    });

    it('stops listening for Escape once removed', () => {
        const wrapper = sidebar(true);
        wrapper.unmount();

        pressKey('Escape');

        expect(wrapper.emitted('update:open')).toBeUndefined();
    });

    it('has a post search box and the account block', async () => {
        const wrapper = sidebar(false);
        const search = wrapper.get<HTMLInputElement>('input[type="search"]');

        await search.setValue('compost');

        expect(search.element.value).toBe('compost');
        expect(wrapper.text()).toContain('Log in');
    });
});
