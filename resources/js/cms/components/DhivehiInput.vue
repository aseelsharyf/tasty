<script setup lang="ts">
import { computed, ref, watch, onMounted, nextTick } from 'vue';
import { useDhivehiKeyboard, type DhivehiLayout } from '../composables/useDhivehiKeyboard';

const props = withDefaults(defineProps<{
    modelValue: string;
    placeholder?: string;
    type?: 'input' | 'textarea';
    rows?: number;
    size?: 'sm' | 'md' | 'lg' | 'xl';
    disabled?: boolean;
    defaultEnabled?: boolean;
    defaultLayout?: DhivehiLayout;
    showToggle?: boolean;
    showLayoutSelector?: boolean;
}>(), {
    type: 'input',
    rows: 3,
    size: 'md',
    disabled: false,
    defaultEnabled: false,
    defaultLayout: 'phonetic',
    showToggle: true,
    showLayoutSelector: false,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

// Reference to the Nuxt UI component
const componentRef = ref<any>(null);
// Reference to the actual HTML input/textarea element
const inputEl = ref<HTMLInputElement | HTMLTextAreaElement | null>(null);

const { enabled, layout, toggle, handleKeyDown, direction, layoutOptions } = useDhivehiKeyboard({
    defaultEnabled: props.defaultEnabled,
    defaultLayout: props.defaultLayout,
});

const fontClass = computed(() => (enabled.value ? 'font-dhivehi' : ''));

// Get the actual HTML input element from the Nuxt UI component
function getInputElement(): HTMLInputElement | HTMLTextAreaElement | null {
    if (componentRef.value) {
        // Try to find the input element within the component
        const el = componentRef.value.$el || componentRef.value;
        if (el instanceof HTMLInputElement || el instanceof HTMLTextAreaElement) {
            return el;
        }
        // Look for input or textarea within the component
        return el.querySelector?.('input, textarea') || null;
    }
    return null;
}

onMounted(() => {
    nextTick(() => {
        inputEl.value = getInputElement();
    });
});

// Re-get element when component updates
watch(componentRef, () => {
    nextTick(() => {
        inputEl.value = getInputElement();
    });
});

function onKeyDown(e: KeyboardEvent) {
    // Get the element directly from the event target as fallback
    const el = inputEl.value || (e.target as HTMLInputElement | HTMLTextAreaElement);
    if (el && (el instanceof HTMLInputElement || el instanceof HTMLTextAreaElement)) {
        handleKeyDown(e, el);
    }
}

function onInput(e: Event) {
    const target = e.target as HTMLInputElement | HTMLTextAreaElement;
    emit('update:modelValue', target.value);
}

// Sync disabled state with keyboard
watch(() => props.disabled, (isDisabled) => {
    if (isDisabled) {
        // Keep keyboard state but don't translate when disabled
    }
});
</script>

<template>
    <div class="dhivehi-input-wrapper w-full">
        <!-- Input Field -->
        <template v-if="type === 'input'">
            <div class="flex items-center gap-2 w-full">
                <UInput
                    ref="componentRef"
                    :model-value="modelValue"
                    :placeholder="placeholder"
                    :size="size"
                    :disabled="disabled"
                    :dir="direction"
                    :class="[fontClass, 'flex-1']"
                    class="w-full"
                    @keydown="onKeyDown"
                    @input="onInput"
                />
                <UButton
                    v-if="showToggle"
                    type="button"
                    :color="enabled ? 'primary' : 'neutral'"
                    :variant="enabled ? 'soft' : 'ghost'"
                    size="sm"
                    square
                    @click="toggle"
                    :title="enabled ? 'Switch to English (EN)' : 'Switch to Dhivehi (DV)'"
                >
                    <span class="text-xs font-medium">{{ enabled ? 'DV' : 'EN' }}</span>
                </UButton>
            </div>
        </template>

        <!-- Textarea Field -->
        <template v-else>
            <div class="w-full">
                <div class="flex items-start gap-2">
                    <UTextarea
                        ref="componentRef"
                        :model-value="modelValue"
                        :placeholder="placeholder"
                        :rows="rows"
                        :disabled="disabled"
                        :dir="direction"
                        :class="[fontClass, 'flex-1']"
                        class="w-full"
                        @keydown="onKeyDown"
                        @input="onInput"
                    />
                    <UButton
                        v-if="showToggle"
                        type="button"
                        :color="enabled ? 'primary' : 'neutral'"
                        :variant="enabled ? 'soft' : 'ghost'"
                        size="sm"
                        square
                        @click="toggle"
                        :title="enabled ? 'Switch to English (EN)' : 'Switch to Dhivehi (DV)'"
                    >
                        <span class="text-xs font-medium">{{ enabled ? 'DV' : 'EN' }}</span>
                    </UButton>
                </div>
            </div>
        </template>

        <!-- Layout Selector -->
        <div v-if="showLayoutSelector && enabled" class="mt-2 flex items-center gap-2">
            <span class="text-xs text-muted">Layout:</span>
            <USelectMenu
                v-model="layout"
                :items="layoutOptions"
                value-key="value"
                size="xs"
                class="w-32"
            />
        </div>
    </div>
</template>

<style>
/* Dhivehi/Thaana font support */
.font-dhivehi {
    font-family: 'MV Faseyha', 'MV Iyyu Nala', 'Faruma', 'MV Waheed', sans-serif;
}
</style>
