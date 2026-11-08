<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { LogOut, Pencil, Settings } from '@lucide/vue';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import Badge from '@/components/ui/Badge.vue';
import BrandIcon from '@/components/ui/BrandIcon.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import NavItem from '@/components/ui/NavItem.vue';
import SegmentedControl from '@/components/ui/SegmentedControl.vue';
import Tag from '@/components/ui/Tag.vue';
import TextLink from '@/components/ui/TextLink.vue';
import Checkbox from '@/components/ui/form/Checkbox.vue';
import FormField from '@/components/ui/form/FormField.vue';
import Input from '@/components/ui/form/Input.vue';
import PasswordInput from '@/components/ui/form/PasswordInput.vue';
import DropdownMenu from '@/components/ui/menu/DropdownMenu.vue';
import MenuItem from '@/components/ui/menu/MenuItem.vue';

interface PaletteColour {
    name: string;
    hex: string;
}

interface ThemeColour {
    name: string;
    foreground: string | null;
    light: PaletteColour;
    dark: PaletteColour;
}

interface PaletteGroup {
    group: string;
    shades: (PaletteColour & { description: string | null })[];
}

interface Font {
    name: string;
    family: string;
    description: string | null;
}

// From app/Http/Controllers/StyleguideController.php, which reads resources/theme/tokens.json.
defineProps<{
    tokensPath: string;
    themeColours: ThemeColour[];
    palette: PaletteGroup[];
    fonts: Font[];
    icons: string[];
}>();

// Demo state for the interactive examples below.
const tabs = ref('circles');
const language = ref('en');
const text = ref('');
const password = ref('');
const checked = ref(false);
</script>

<template>
    <AppLayout>
        <Head title="Styleguide" />

        <header class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-4xl font-extrabold">Styleguide</h1>
                <p class="text-muted-foreground">
                    Every colour comes from <code class="rounded bg-secondary px-1.5 py-0.5 text-sm">{{ tokensPath }}</code>. Switch the theme to check both
                    modes.
                </p>
            </div>
            <ThemeToggle />
        </header>

        <Card title="Theme colours" description="Use these in components (bg-primary, text-muted-foreground, border-border…). Values switch with dark mode.">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <div v-for="swatch in themeColours" :key="swatch.name" class="flex items-center gap-3">
                    <div
                        class="grid size-14 shrink-0 place-items-center rounded-control border border-border font-heading text-lg font-bold"
                        :style="{ background: `var(--${swatch.name})`, color: swatch.foreground ? `var(--${swatch.foreground})` : undefined }"
                    >
                        <span v-if="swatch.foreground">Aa</span>
                    </div>
                    <div class="flex min-w-0 flex-col text-sm">
                        <span class="font-semibold text-heading">{{ swatch.name }}</span>
                        <span class="text-muted-foreground">light {{ swatch.light.name }} · {{ swatch.light.hex }}</span>
                        <span class="text-muted-foreground">dark {{ swatch.dark.name }} · {{ swatch.dark.hex }}</span>
                    </div>
                </div>
            </div>
        </Card>

        <Card title="Palette" description="Raw brand colours. Prefer the theme colours above in components; these exist for one-off illustration work.">
            <div class="flex flex-col gap-5">
                <div v-for="group in palette" :key="group.group" class="flex flex-col gap-2">
                    <h4 class="text-sm font-extrabold tracking-[0.12em] text-muted-foreground uppercase">{{ group.group }}</h4>
                    <div class="flex flex-wrap gap-3">
                        <div v-for="shade in group.shades" :key="shade.name" class="flex w-28 flex-col gap-1 text-xs" :title="shade.description ?? undefined">
                            <div class="h-12 rounded-control border border-border" :style="{ background: shade.hex }" />
                            <span class="font-semibold text-heading">{{ shade.name }}</span>
                            <span class="text-muted-foreground">{{ shade.hex }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </Card>

        <Card title="Typography">
            <div class="flex flex-col gap-4">
                <h1 class="text-4xl font-extrabold">Heading 1 · Nunito ExtraBold</h1>
                <h2 class="text-3xl font-bold">Heading 2 · Nunito Bold</h2>
                <h3 class="text-xl font-bold">Heading 3 · Nunito Bold</h3>
                <p>Body text in Nunito Sans. Join a Circle, ask the people already farming, building and living in community.</p>
                <p class="text-muted-foreground">Muted text for descriptions and secondary information.</p>
                <p class="text-subtle-foreground">Subtle text for disabled or coming-soon items.</p>
                <p class="font-display text-xl tracking-wide text-heading uppercase">Display label · Playfair Display</p>
                <ul class="flex flex-col gap-1 text-sm text-muted-foreground">
                    <li v-for="font in fonts" :key="font.name">
                        <code>font-{{ font.name }}</code>: {{ font.family }} — {{ font.description }}
                    </li>
                </ul>
            </div>
        </Card>

        <Card title="Radius">
            <div class="flex flex-wrap gap-4 text-sm text-muted-foreground">
                <div class="grid h-20 w-32 place-items-center rounded-control bg-secondary">rounded-control</div>
                <div class="grid h-20 w-32 place-items-center rounded-card bg-secondary">rounded-card</div>
                <div class="grid h-20 w-32 place-items-center rounded-panel bg-secondary">rounded-panel</div>
                <div class="grid h-20 w-32 place-items-center rounded-full bg-secondary">rounded-full</div>
            </div>
        </Card>

        <Card title="Buttons">
            <div class="flex flex-col gap-4">
                <div v-for="size in ['sm', 'md', 'lg']" :key="size" class="flex flex-wrap items-center gap-3">
                    <Button v-for="variant in ['primary', 'secondary', 'accent', 'surface', 'ghost']" :key="variant" :variant="variant" :size="size">
                        {{ variant }} {{ size }}
                    </Button>
                </div>
                <div class="flex flex-wrap gap-3">
                    <Button disabled>Disabled</Button>
                    <Button variant="accent" disabled>Disabled</Button>
                </div>
                <div class="flex flex-wrap gap-3 rounded-panel bg-brand p-6">
                    <Button variant="accent" size="lg">Accent on brand</Button>
                    <Button variant="surface" size="lg">Surface on brand</Button>
                </div>
            </div>
        </Card>

        <Card title="Form controls" description="Wrap a control in FormField for its label, hint and error; it links them to the control for screen readers.">
            <div class="flex max-w-md flex-col gap-5">
                <Input v-model="text" placeholder="Placeholder text" aria-label="Example input" />
                <Input placeholder="Disabled" aria-label="Disabled example input" disabled />
                <FormField v-slot="field" label="Field with a hint" hint="Help text sits under the control.">
                    <Input v-bind="field" v-model="text" />
                </FormField>
                <FormField v-slot="field" label="Field with an error" error="This field is required.">
                    <Input v-bind="field" />
                </FormField>
                <FormField v-slot="field" label="Password">
                    <PasswordInput v-bind="field" v-model="password" autocomplete="off" />
                </FormField>
                <label class="flex cursor-pointer items-center gap-3 self-start">
                    <Checkbox v-model="checked" />
                    Checkbox with its label
                </label>
            </div>
        </Card>

        <Card title="Menus" description="DropdownMenu opens a list of MenuItems from a button. Arrow keys move between items; Escape or a click elsewhere closes it.">
            <div class="flex gap-6">
                <DropdownMenu label="Example menu">
                    <template #trigger>
                        <Settings class="size-5" aria-hidden="true" />
                    </template>
                    <MenuItem>
                        <Pencil class="size-4" aria-hidden="true" />
                        Edit
                    </MenuItem>
                    <MenuItem>
                        <LogOut class="size-4" aria-hidden="true" />
                        Log out
                    </MenuItem>
                </DropdownMenu>
            </div>
        </Card>

        <Card title="Tags, badges and links">
            <div class="flex flex-col gap-4">
                <div class="flex flex-wrap gap-2">
                    <Tag>plain-tag</Tag>
                    <Tag :href="route('home')">linked-tag</Tag>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Badge v-for="variant in ['primary', 'secondary', 'accent', 'success', 'warning', 'danger']" :key="variant" :variant="variant">
                        {{ variant }}
                    </Badge>
                </div>
                <p>Text with an <TextLink href="#" external>inline link</TextLink> in it.</p>
            </div>
        </Card>

        <Card title="Segmented controls">
            <div class="flex flex-col gap-4">
                <SegmentedControl
                    v-model="tabs"
                    block
                    label="Example tabs"
                    :options="[
                        { value: 'one', label: 'Tab one' },
                        { value: 'circles', label: 'Tab two' },
                    ]"
                />
                <SegmentedControl
                    v-model="language"
                    shape="pill"
                    label="Example pill switch"
                    :options="[
                        { value: 'en', label: 'Option one' },
                        { value: 'sw', label: 'Option two' },
                    ]"
                />
                <div class="flex items-center gap-3 rounded-control bg-brand p-3">
                    <ThemeToggle tone="brand" />
                    <span class="text-sm text-brand-foreground">tone="brand" for the header bar</span>
                </div>
            </div>
        </Card>

        <Card title="Navigation and icons">
            <div class="grid gap-6 lg:grid-cols-2">
                <nav aria-label="Example navigation" class="flex flex-col gap-1">
                    <NavItem :icon="icons[0]" active>Active item</NavItem>
                    <NavItem :icon="icons[1]">Inactive item</NavItem>
                </nav>
                <div class="flex flex-wrap gap-4">
                    <div v-for="icon in icons" :key="icon" class="flex w-20 flex-col items-center gap-1 text-center text-xs text-muted-foreground">
                        <BrandIcon :name="icon" :size="56" />
                        {{ icon }}
                    </div>
                    <div class="flex w-20 flex-col items-center gap-1 text-center text-xs text-muted-foreground">
                        <BrandIcon :name="icons[0]" :size="56" disabled />
                        disabled
                    </div>
                </div>
            </div>
        </Card>
    </AppLayout>
</template>
