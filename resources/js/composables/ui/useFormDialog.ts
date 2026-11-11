import type { FormDataType, Method, UseFormSubmitOptions } from '@inertiajs/core';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { Ref } from 'vue';

/**
 * A dialog holding an Inertia form: whether it's open (its own, or one it's given, e.g. a v-model), the form, and
 * send(), which submits the form and, once that succeeds, closes the dialog and resets the form before running any
 * onSuccess of the caller's. Options like preserveScroll are the caller's to pass.
 */
export function useFormDialog<TForm extends FormDataType<TForm>>(data: TForm, open: Ref<boolean> = ref(false)) {
    const form = useForm(data);

    function send(method: Method, url: string, options: UseFormSubmitOptions = {}): void {
        form.submit(method, url, {
            ...options,
            onSuccess: (page) => {
                open.value = false;
                form.reset();
                options.onSuccess?.(page);
            },
        });
    }

    return { open, form, send };
}
