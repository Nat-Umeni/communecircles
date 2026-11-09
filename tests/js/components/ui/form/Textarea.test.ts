import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Textarea from '@/components/ui/form/Textarea.vue';

describe('Textarea', () => {
    it('shows its value and reports typing through v-model', async () => {
        const wrapper = mount(Textarea, { props: { modelValue: 'Share skills.' } });

        expect(wrapper.get<HTMLTextAreaElement>('textarea').element.value).toBe('Share skills.');

        await wrapper.get('textarea').setValue('Share skills and lend tools.');

        expect(wrapper.emitted('update:modelValue')).toEqual([['Share skills and lend tools.']]);
    });

    it('starts empty and passes attributes through to the textarea', () => {
        const wrapper = mount(Textarea, { attrs: { name: 'description', rows: '4', maxlength: '500' } });

        expect(wrapper.get<HTMLTextAreaElement>('textarea').element.value).toBe('');
        expect(wrapper.get('textarea').attributes()).toMatchObject({ name: 'description', rows: '4', maxlength: '500' });
    });
});
