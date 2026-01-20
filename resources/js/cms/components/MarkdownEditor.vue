<script setup lang="ts">
import { Codemirror } from 'vue-codemirror';
import { markdown } from '@codemirror/lang-markdown';
import { oneDark } from '@codemirror/theme-one-dark';
import { EditorView } from 'codemirror';
import { computed, ref, watch } from 'vue';
import MarkdownIt from 'markdown-it';

const props = withDefaults(defineProps<{
    modelValue: string;
    placeholder?: string;
    disabled?: boolean;
    height?: string;
}>(), {
    placeholder: 'Write your content in Markdown...',
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

const showPreview = ref(false);
const md = new MarkdownIt({
    html: true,
    breaks: true,
    linkify: true,
    typographer: true,
});

const renderedHtml = computed(() => {
    return md.render(props.modelValue || '');
});

// Configure extensions
const extensions = computed(() => {
    const exts = [];

    exts.push(markdown());
    exts.push(oneDark);
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

// Helper to insert markdown syntax
function insertMarkdown(prefix: string, suffix: string = '') {
    const textarea = document.querySelector('.cm-content') as HTMLElement;
    if (textarea) {
        const selection = window.getSelection()?.toString() || '';
        const newValue = selection
            ? code.value.replace(selection, `${prefix}${selection}${suffix}`)
            : code.value + `${prefix}${suffix}`;
        emit('update:modelValue', newValue);
    }
}
</script>

<template>
    <div class="markdown-editor-wrapper">
        <!-- Toolbar -->
        <div class="flex items-center gap-1 p-2 border-b border-default bg-elevated">
            <div class="flex items-center gap-1">
                <UButton
                    icon="i-lucide-heading-1"
                    color="neutral"
                    variant="ghost"
                    size="xs"
                    title="Heading 1"
                    @click="insertMarkdown('# ', '')"
                />
                <UButton
                    icon="i-lucide-heading-2"
                    color="neutral"
                    variant="ghost"
                    size="xs"
                    title="Heading 2"
                    @click="insertMarkdown('## ', '')"
                />
                <UButton
                    icon="i-lucide-heading-3"
                    color="neutral"
                    variant="ghost"
                    size="xs"
                    title="Heading 3"
                    @click="insertMarkdown('### ', '')"
                />
            </div>

            <div class="w-px h-5 bg-default" />

            <div class="flex items-center gap-1">
                <UButton
                    icon="i-lucide-bold"
                    color="neutral"
                    variant="ghost"
                    size="xs"
                    title="Bold"
                    @click="insertMarkdown('**', '**')"
                />
                <UButton
                    icon="i-lucide-italic"
                    color="neutral"
                    variant="ghost"
                    size="xs"
                    title="Italic"
                    @click="insertMarkdown('*', '*')"
                />
                <UButton
                    icon="i-lucide-strikethrough"
                    color="neutral"
                    variant="ghost"
                    size="xs"
                    title="Strikethrough"
                    @click="insertMarkdown('~~', '~~')"
                />
            </div>

            <div class="w-px h-5 bg-default" />

            <div class="flex items-center gap-1">
                <UButton
                    icon="i-lucide-list"
                    color="neutral"
                    variant="ghost"
                    size="xs"
                    title="Unordered List"
                    @click="insertMarkdown('- ', '')"
                />
                <UButton
                    icon="i-lucide-list-ordered"
                    color="neutral"
                    variant="ghost"
                    size="xs"
                    title="Ordered List"
                    @click="insertMarkdown('1. ', '')"
                />
                <UButton
                    icon="i-lucide-check-square"
                    color="neutral"
                    variant="ghost"
                    size="xs"
                    title="Checklist"
                    @click="insertMarkdown('- [ ] ', '')"
                />
            </div>

            <div class="w-px h-5 bg-default" />

            <div class="flex items-center gap-1">
                <UButton
                    icon="i-lucide-link"
                    color="neutral"
                    variant="ghost"
                    size="xs"
                    title="Link"
                    @click="insertMarkdown('[', '](url)')"
                />
                <UButton
                    icon="i-lucide-image"
                    color="neutral"
                    variant="ghost"
                    size="xs"
                    title="Image"
                    @click="insertMarkdown('![alt](', ')')"
                />
                <UButton
                    icon="i-lucide-quote"
                    color="neutral"
                    variant="ghost"
                    size="xs"
                    title="Blockquote"
                    @click="insertMarkdown('> ', '')"
                />
                <UButton
                    icon="i-lucide-code"
                    color="neutral"
                    variant="ghost"
                    size="xs"
                    title="Inline Code"
                    @click="insertMarkdown('`', '`')"
                />
                <UButton
                    icon="i-lucide-file-code"
                    color="neutral"
                    variant="ghost"
                    size="xs"
                    title="Code Block"
                    @click="insertMarkdown('\n```\n', '\n```\n')"
                />
            </div>

            <div class="w-px h-5 bg-default" />

            <UButton
                icon="i-lucide-minus"
                color="neutral"
                variant="ghost"
                size="xs"
                title="Horizontal Rule"
                @click="insertMarkdown('\n---\n', '')"
            />

            <div class="flex-1" />

            <!-- Preview toggle -->
            <UButton
                :icon="showPreview ? 'i-lucide-pencil' : 'i-lucide-eye'"
                :color="showPreview ? 'primary' : 'neutral'"
                :variant="showPreview ? 'soft' : 'ghost'"
                size="xs"
                @click="showPreview = !showPreview"
            >
                {{ showPreview ? 'Edit' : 'Preview' }}
            </UButton>
        </div>

        <!-- Editor / Preview -->
        <div class="editor-content" :style="{ height }">
            <div v-if="!showPreview" class="h-full">
                <Codemirror
                    v-model="code"
                    :placeholder="placeholder"
                    :extensions="extensions"
                    :disabled="disabled"
                />
            </div>
            <div
                v-else
                class="markdown-preview h-full overflow-auto p-6 prose prose-sm dark:prose-invert max-w-none"
                v-html="renderedHtml"
            />
        </div>

        <!-- Help text -->
        <div class="px-3 py-2 text-xs text-muted border-t border-default bg-elevated/50">
            <span class="font-medium">Markdown supported</span> - Use **bold**, *italic*, # headings, - lists, [links](url), ![images](url)
        </div>
    </div>
</template>

<style scoped>
.markdown-editor-wrapper {
    position: relative;
    width: 100%;
    border-radius: 0.5rem;
    border: 1px solid var(--ui-border);
    overflow: hidden;
    contain: inline-size;
    background: var(--ui-bg);
}

.editor-content {
    position: relative;
}

.editor-content :deep(.cm-editor) {
    width: 100% !important;
    max-width: 100% !important;
    height: 100%;
}

.editor-content :deep(.cm-scroller) {
    overflow: auto !important;
}

.editor-content :deep(.cm-content) {
    max-width: 100%;
}

.editor-content :deep(.cm-line) {
    white-space: pre !important;
}

.markdown-preview {
    background: var(--ui-bg-elevated);
}

.markdown-preview :deep(h1) {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 1rem;
    border-bottom: 1px solid var(--ui-border);
    padding-bottom: 0.5rem;
}

.markdown-preview :deep(h2) {
    font-size: 1.5rem;
    font-weight: 600;
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
}

.markdown-preview :deep(h3) {
    font-size: 1.25rem;
    font-weight: 600;
    margin-top: 1.25rem;
    margin-bottom: 0.5rem;
}

.markdown-preview :deep(p) {
    margin-bottom: 1rem;
    line-height: 1.75;
}

.markdown-preview :deep(ul),
.markdown-preview :deep(ol) {
    margin-bottom: 1rem;
    padding-left: 1.5rem;
}

.markdown-preview :deep(li) {
    margin-bottom: 0.25rem;
}

.markdown-preview :deep(blockquote) {
    border-left: 4px solid var(--ui-primary);
    padding-left: 1rem;
    margin: 1rem 0;
    font-style: italic;
    color: var(--ui-text-muted);
}

.markdown-preview :deep(code) {
    background: var(--ui-bg-muted);
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    font-size: 0.875em;
}

.markdown-preview :deep(pre) {
    background: var(--ui-bg-muted);
    padding: 1rem;
    border-radius: 0.5rem;
    overflow-x: auto;
    margin: 1rem 0;
}

.markdown-preview :deep(pre code) {
    background: none;
    padding: 0;
}

.markdown-preview :deep(a) {
    color: var(--ui-primary);
    text-decoration: underline;
}

.markdown-preview :deep(img) {
    max-width: 100%;
    height: auto;
    border-radius: 0.5rem;
}

.markdown-preview :deep(hr) {
    border: none;
    border-top: 1px solid var(--ui-border);
    margin: 2rem 0;
}

.markdown-preview :deep(table) {
    width: 100%;
    border-collapse: collapse;
    margin: 1rem 0;
}

.markdown-preview :deep(th),
.markdown-preview :deep(td) {
    border: 1px solid var(--ui-border);
    padding: 0.5rem 0.75rem;
    text-align: left;
}

.markdown-preview :deep(th) {
    background: var(--ui-bg-muted);
    font-weight: 600;
}
</style>
