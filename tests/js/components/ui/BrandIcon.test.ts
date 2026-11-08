import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import BrandIcon from '@/components/ui/BrandIcon.vue';

describe('BrandIcon', () => {
    it('shows the named icon from public/images/icons at 40px, decorative by default', () => {
        const wrapper = mount(BrandIcon, { props: { name: 'explore' } });

        expect(wrapper.attributes('src')).toBe('/images/icons/explore.png');
        expect(wrapper.attributes('width')).toBe('40');
        expect(wrapper.attributes('height')).toBe('40');
        expect(wrapper.attributes('alt')).toBe('');
        expect(wrapper.classes()).not.toContain('opacity-50');
    });

    it('takes a size and alt text, and fades out when disabled', () => {
        const wrapper = mount(BrandIcon, { props: { name: 'seeds-plants', size: 88, alt: 'Seeds and plants', disabled: true } });

        expect(wrapper.attributes('width')).toBe('88');
        expect(wrapper.attributes('alt')).toBe('Seeds and plants');
        expect(wrapper.classes()).toContain('opacity-50');
    });
});
