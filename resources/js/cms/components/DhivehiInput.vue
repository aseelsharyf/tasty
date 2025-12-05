<script setup lang="ts">
import { computed, ref, watch } from 'vue';
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

const inputRef = ref<HTMLInputElement | HTMLTextAreaElement | null>(null);

const { enabled, layout, toggle, handleKeyDown, direction, layoutOptions } = useDhivehiKeyboard({
    defaultEnabled: props.defaultEnabled,
    defaultLayout: props.defaultLayout,
});

const fontClass = computed(() => (enabled.value ? 'font-dhivehi' : ''));

function onKeyDown(e: KeyboardEvent) {
    if (inputRef.value) {
        handleKeyDown(e, inputRef.value);
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
    <div class="dhivehi-input-wrapper relative">
        <!-- Input Field -->
        <template v-if="type === 'input'">
            <UInput
                ref="inputRef"
                :model-value="modelValue"
                :placeholder="placeholder"
                :size="size"
                :disabled="disabled"
                :dir="direction"
                :class="fontClass"
                :ui="{ trailing: showToggle ? 'pe-10' : undefined }"
                @keydown="onKeyDown"
                @input="onInput"
            >
                <template v-if="showToggle" #trailing>
                    <button
                        type="button"
                        class="p-1 rounded hover:bg-muted/50 transition-colors"
                        :class="{ 'text-primary': enabled, 'text-muted': !enabled }"
                        @click="toggle"
                        :title="enabled ? 'Switch to English' : 'Switch to Dhivehi'"
                    >
                        <UIcon
                            :name="enabled ? 'i-lucide-languages' : 'i-lucide-keyboard'"
                            class="size-4"
                        />
                    </button>
                </template>
            </UInput>
        </template>

        <!-- Textarea Field -->
        <template v-else>
            <div class="relative">
                <UTextarea
                    ref="inputRef"
                    :model-value="modelValue"
                    :placeholder="placeholder"
                    :rows="rows"
                    :disabled="disabled"
                    :dir="direction"
                    :class="fontClass"
                    @keydown="onKeyDown"
                    @input="onInput"
                />
                <button
                    v-if="showToggle"
                    type="button"
                    class="absolute top-2 right-2 p-1 rounded hover:bg-muted/50 transition-colors"
                    :class="{ 'text-primary': enabled, 'text-muted': !enabled }"
                    @click="toggle"
                    :title="enabled ? 'Switch to English' : 'Switch to Dhivehi'"
                >
                    <UIcon
                        :name="enabled ? 'i-lucide-languages' : 'i-lucide-keyboard'"
                        class="size-4"
                    />
                </button>
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
