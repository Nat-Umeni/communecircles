<script setup lang="ts">
import { Eye, EyeOff } from '@lucide/vue';
import { ref } from 'vue';
import Input from '@/components/ui/form/Input.vue';

// Attributes such as id, name and autocomplete belong on the <input>, not the wrapper.
defineOptions({ inheritAttrs: false });

const model = defineModel<string>({ default: '' });
const visible = ref(false);
</script>

<template>
    <div class="relative">
        <Input v-model="model" v-bind="$attrs" :type="visible ? 'text' : 'password'" class="pr-12" />
        <button
            type="button"
            class="absolute inset-y-0 right-0 grid w-12 place-items-center rounded-r-control text-muted-foreground transition-colors hover:text-heading focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            aria-label="Show password"
            :aria-pressed="visible"
            @click="visible = !visible"
        >
            <component :is="visible ? EyeOff : Eye" class="size-5" aria-hidden="true" />
        </button>
    </div>
</template>
