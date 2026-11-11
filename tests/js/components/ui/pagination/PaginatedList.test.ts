import { mount, type VueWrapper } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vitest';
import PaginatedList from '@/components/ui/pagination/PaginatedList.vue';
import Pagination from '@/components/ui/pagination/Pagination.vue';
import type { Paginated } from '@/types';
import { paginated } from '@tests/support';

function list(paginator: Paginated<unknown>, props: { size?: 'card' | 'page'; only?: string[] } = {}): VueWrapper {
    return mount(PaginatedList, {
        props: { paginator, label: 'Members', ...props },
        slots: { default: '<ul data-items><li>Grace Hopper</li></ul>' },
    });
}

describe('PaginatedList', () => {
    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('puts the list in a scroll area named for it, which keyboard users can scroll too', () => {
        const area = list(paginated(['a'])).get('[role="region"]');

        expect(area.attributes('aria-label')).toBe('Members');
        expect(area.attributes('tabindex')).toBe('0');
        expect(area.find('[data-items]').exists()).toBe(true);
        expect(area.classes()).toContain('overflow-auto');
    });

    it('keeps the list a card or a page high', () => {
        expect(list(paginated(['a'])).get('[role="region"]').classes()).toContain('max-h-96');
        expect(list(paginated(['a']), { size: 'page' }).get('[role="region"]').classes()).toContain('max-h-[70vh]');
    });

    it('puts its pages below the list, named for it, reloading only the list it is told to', () => {
        const wrapper = list(paginated(['a'], { current_page: 1, last_page: 2 }), { only: ['members'] });

        expect(wrapper.getComponent(Pagination).props()).toEqual(
            expect.objectContaining({ label: 'Members pages', only: ['members'], paginator: expect.objectContaining({ current_page: 1 }) }),
        );
        expect(wrapper.find('[role="region"] nav').exists()).toBe(false);
    });

    it('glides the list back to its top, and its top into view clear of the header, when another page arrives', async () => {
        const wrapper = list(paginated(['a'], { current_page: 1, last_page: 2 }));
        const area = wrapper.get<HTMLElement>('[role="region"]');
        const scrollTo = vi.spyOn(area.element, 'scrollTo');
        const scrollIntoView = vi.spyOn(area.element, 'scrollIntoView');

        await wrapper.setProps({ paginator: paginated(['a', 'b'], { current_page: 1, last_page: 2 }) });
        expect(scrollTo).not.toHaveBeenCalled();
        expect(scrollIntoView).not.toHaveBeenCalled();

        await wrapper.setProps({ paginator: paginated(['c'], { current_page: 2, last_page: 2 }) });
        expect(scrollTo).toHaveBeenCalledExactlyOnceWith({ top: 0, behavior: 'smooth' });
        expect(scrollIntoView).toHaveBeenCalledExactlyOnceWith({ block: 'nearest', behavior: 'smooth' });
        expect(area.classes()).toContain('scroll-mt-24');
    });

    it('moves straight there for someone who prefers less motion', async () => {
        vi.spyOn(window, 'matchMedia').mockImplementation((query) => ({ matches: query === '(prefers-reduced-motion: reduce)' }) as MediaQueryList);
        const wrapper = list(paginated(['a'], { current_page: 1, last_page: 2 }));
        const area = wrapper.get<HTMLElement>('[role="region"]').element;
        const scrollTo = vi.spyOn(area, 'scrollTo');
        const scrollIntoView = vi.spyOn(area, 'scrollIntoView');

        await wrapper.setProps({ paginator: paginated(['c'], { current_page: 2, last_page: 2 }) });

        expect(scrollTo).toHaveBeenCalledWith({ top: 0, behavior: 'auto' });
        expect(scrollIntoView).toHaveBeenCalledWith({ block: 'nearest', behavior: 'auto' });
    });
});
