<script setup lang="ts">
import { computed, useId } from 'vue';
import InputError from '@/components/ui/form/InputError.vue';
import Label from '@/components/ui/form/Label.vue';

const props = defineProps<{
    label: string;
    /** Help shown under the control, e.g. the password rules. */
    hint?: string;
    /** The field's validation message, e.g. form.errors.email. */
    error?: string;
}>();

const id = useId();
const hintId = `${id}-hint`;
const errorId = `${id}-error`;

// Bound onto the control through the slot, so the label, hint and error are announced with it.
const control = computed(() => ({
    id,
    'aria-invalid': props.error ? true : undefined,
    'aria-describedby': [props.hint && hintId, props.error && errorId].filter(Boolean).join(' ') || undefined,
}));
</script>

<template>
    <div class="flex flex-col gap-2">
        <Label :for="id">{{ label }}</Label>
        <slot v-bind="control" />
        <p v-if="hint" :id="hintId" class="text-sm text-muted-foreground">{{ hint }}</p>
        <InputError :id="errorId" :message="error" />
    </div>
</template>
