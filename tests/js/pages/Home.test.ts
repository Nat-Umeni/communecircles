import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Home from '@/pages/Home.vue';
import { groupButtons, links, locales, signIn } from '@tests/support';

const circles = [
    { name: 'Commune Circles', description: 'About the site itself.', channels: ['announcements'] },
    { name: 'Exchange', description: 'Share skills and lend tools.', channels: ['skills', 'give-away'] },
];

function home() {
    return mount(Home, { props: { circles, locale: 'en', locales } });
}

describe('Home', () => {
    it('shows the circles feed by default', () => {
        const wrapper = home();

        expect(wrapper.findAll('main h3').map((heading) => heading.text())).toEqual(['Commune Circles', 'Exchange']);
        expect(groupButtons(wrapper, 'Feed')[1].attributes('aria-pressed')).toBe('true');
    });

    it('switches to the Latest tab, which is empty for now', async () => {
        const wrapper = home();

        await groupButtons(wrapper, 'Feed')[0].trigger('click');

        expect(wrapper.text()).toContain('No posts yet');
        expect(wrapper.text()).not.toContain('Share skills and lend tools.');
    });

    it('invites guests to log in or join', () => {
        expect(links(home().get('main section'))).toEqual([
            ['Log in', '/login'],
            ['Join the Circle', '/auth.register.create'],
        ]);
    });

    it('offers members the explore button instead', () => {
        signIn();
        const wrapper = home();

        expect(wrapper.get('main section button').text()).toBe('Explore communes');
        expect(wrapper.find('main section a').exists()).toBe(false);
    });

    it('starts the language switch on the current language and lets it change', async () => {
        const wrapper = home();
        const languages = () => groupButtons(wrapper, 'Language');

        expect(languages()[0].attributes('aria-pressed')).toBe('true');

        await languages()[1].trigger('click');

        expect(languages()[1].attributes('aria-pressed')).toBe('true');
    });

    it('links to the community rules', () => {
        expect(home().get('footer a').text()).toBe('Community rules');
    });
});
