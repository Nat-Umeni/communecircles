import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import SidebarAccount from '@/components/account/SidebarAccount.vue';
import { signIn } from '../../support/page';
import { fakeRouter } from '../../support/router';

describe('SidebarAccount', () => {
    it('invites guests to log in or register', () => {
        const wrapper = mount(SidebarAccount);
        const links = wrapper.findAll('a');

        expect(wrapper.text()).toContain('Join to ask questions');
        expect(links.map((link) => [link.text(), link.attributes('href')])).toEqual([
            ['Log in', '/login'],
            ['Register', '/auth.register.create'],
        ]);
    });

    it("shows a member's name and email, with no badge for an ordinary member", () => {
        signIn({ first_name: 'Ada', last_name: 'Lovelace', email: 'ada@example.com' });
        const wrapper = mount(SidebarAccount);

        expect(wrapper.text()).toContain('Ada Lovelace');
        expect(wrapper.text()).toContain('ada@example.com');
        expect(wrapper.find('.uppercase').exists()).toBe(false);
    });

    it.each([
        ['admin', 'Admin', 'bg-accent'],
        ['super_admin', 'Super Admin', 'bg-primary'],
    ] as const)('shows the %s badge with the label from PHP', (value, label, colour) => {
        signIn({ role: { value, label } });
        const badge = mount(SidebarAccount).get('.uppercase');

        expect(badge.text()).toBe(label);
        expect(badge.classes()).toContain(colour);
    });

    it('logs out from the account menu', async () => {
        signIn();
        const wrapper = mount(SidebarAccount, { attachTo: document.body });

        await wrapper.get('[aria-haspopup="menu"]').trigger('click');
        await wrapper.get('[role="menuitem"]').trigger('click');

        expect(fakeRouter.visit).toHaveBeenCalledWith('/logout', expect.objectContaining({ method: 'post' }));
        wrapper.unmount();
    });
});
