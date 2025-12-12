<script setup lang="ts">
import { Codemirror } from 'vue-codemirror';
import { html } from '@codemirror/lang-html';
import { php } from '@codemirror/lang-php';
import { oneDark } from '@codemirror/theme-one-dark';
import { EditorView } from 'codemirror';
import { computed } from 'vue';

const props = withDefaults(defineProps<{
    modelValue: string;
    language?: 'html' | 'php' | 'blade';
    placeholder?: string;
    disabled?: boolean;
    height?: string;
}>(), {
    language: 'blade',
    placeholder: 'Enter your code here...',
    disabled: false,
    height: '500px',
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const code = computed({
    get: () => props.modelValue || '',
    set: (value: string) => emit('update:modelValue', value),
});

// Configure extensions based on language
const extensions = computed(() => {
    const exts = [];

    // Language support - PHP includes HTML for Blade templates
    if (props.language === 'php' || props.language === 'blade') {
        exts.push(php());
    } else {
        exts.push(html());
    }

    // Theme
    exts.push(oneDark);

    // Custom styling with fixed height
    exts.push(EditorView.theme({
        '&': {
            height: props.height,
            fontSize: '14px',
        },
        '.cm-scroller': {
            overflow: 'auto',
        },
        '&.cm-focused': {
            outline: 'none',
        },
    }));

    return exts;
});
</script>

<template>
    <div class="code-editor-wrapper" :style="{ height }">
        <Codemirror
            v-model="code"
            :placeholder="placeholder"
            :extensions="extensions"
            :disabled="disabled"
        />
    </div>
</template>

<style scoped>
.code-editor-wrapper {
    position: relative;
    width: 100%;
    border-radius: 0.5rem;
    border: 1px solid var(--ui-border);
    overflow: hidden;
    contain: inline-size;
}

.code-editor-wrapper :deep(.cm-editor) {
    width: 100% !important;
    max-width: 100% !important;
    height: 100%;
    border-radius: 0.5rem;
}

.code-editor-wrapper :deep(.cm-scroller) {
    overflow: auto !important;
}

.code-editor-wrapper :deep(.cm-content) {
    max-width: 100%;
}

.code-editor-wrapper :deep(.cm-gutters) {
    border-radius: 0.5rem 0 0 0.5rem;
}

.code-editor-wrapper :deep(.cm-line) {
    white-space: pre !important;
}
</style>
