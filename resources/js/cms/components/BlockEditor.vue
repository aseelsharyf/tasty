<script setup lang="ts">
import { ref, watch, onMounted, onBeforeUnmount, shallowRef, computed } from 'vue';
import EditorJS, { type OutputData } from '@editorjs/editorjs';
import Header from '@editorjs/header';
import List from '@editorjs/list';
import Quote from '@editorjs/quote';
import Delimiter from '@editorjs/delimiter';
import Table from '@editorjs/table';
import Code from '@editorjs/code';
import MediaBlock, { type MediaBlockItem } from '../editor-tools/MediaBlock';
import '../editor-tools/media-block.css';
import type { DhivehiLayout } from '../composables/useDhivehiKeyboard';

// Media selection callback type
export type MediaSelectCallback = (options: { multiple: boolean }) => Promise<MediaBlockItem[] | null>;

// Character mappings from JTK library (duplicated here for editor use)
const TRANS_FROM = "qwertyuiop[]\\asdfghjkl;'zxcvbnm,./QWERTYUIOP{}|ASDFGHJKL:\"ZXCVBNM<>?()";
const LAYOUTS: Record<DhivehiLayout, string> = {
    phonetic: "ްއެރތޔުިޮޕ][\\ަސދފގހޖކލ؛'ޒ×ޗވބނމ،./ޤޢޭޜޓޠޫީޯ÷}{|ާށޑﷲޣޙޛޚޅ:\"ޡޘޝޥޞޏޟ><؟)(",
    'phonetic-hh': "ޤަެރތޔުިޮޕ][\\އސދފގހޖކލ؛'ޒޝްވބނމ،./ﷲާޭޜޓޠޫީޯޕ}{|ޢށޑޟޣޙޛޚޅ:\"ޡޘޗޥޞޏމ><؟)(",
    typewriter: "ޫޮާީޭގރމތހލ[]ިުްަެވއނކފﷲޒޑސޔޅދބށޓޯ×'\"/:ޤޜޣޠޙ÷{}<>.،\"ޥޢޘޚޡ؛ޖޕޏޗޟޛޝ\\ޞ؟)(",
};

const props = defineProps<{
    modelValue?: OutputData | null;
    placeholder?: string;
    readOnly?: boolean;
    rtl?: boolean;
    dhivehiEnabled?: boolean;
    dhivehiLayout?: DhivehiLayout;
    onSelectMedia?: MediaSelectCallback;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: OutputData): void;
}>();

const editorRef = ref<HTMLElement | null>(null);
const editor = shallowRef<EditorJS | null>(null);
const isReady = ref(false);
// Track if change originated from editor to prevent re-render loops
const isInternalChange = ref(false);

// Dhivehi keyboard translation for contenteditable
const currentLayout = computed(() => props.dhivehiLayout || 'phonetic');

function translateChar(char: string): string {
    if (!props.dhivehiEnabled) return char;

    const index = TRANS_FROM.indexOf(char);
    if (index === -1) return char;

    const layoutChars = Array.from(LAYOUTS[currentLayout.value]);
    return layoutChars[index] || char;
}

function handleEditorKeyDown(e: KeyboardEvent): void {
    // Skip if Dhivehi not enabled or modifier keys are pressed
    if (!props.dhivehiEnabled || e.ctrlKey || e.metaKey || e.altKey) return;

    const char = e.key;
    // Only translate single printable characters
    if (char.length !== 1) return;

    const translated = translateChar(char);
    // If no translation needed, let the browser handle it
    if (translated === char) return;

    e.preventDefault();
    e.stopPropagation();

    // Use execCommand to insert text - this is recognized by contenteditable and Editor.js
    // execCommand is deprecated but still works and properly triggers Editor.js updates
    document.execCommand('insertText', false, translated);
}

// Dhivehi translations for placeholders
const dhivehiPlaceholders = {
    default: 'ލިޔަން ފަށާ...', // Start writing...
    header: 'ސުރުޚީ', // Header
    quote: 'ބަސްކޮޅެއް ލިޔޭ', // Enter a quote
    quoteCaption: 'ބުނީ ކާކު؟', // Who said it?
    code: 'ކޯޑު ނުވަތަ ރެސިޕީ ޓިޕްސް...', // Code or recipe tips...
};

const initEditor = async () => {
    if (!editorRef.value || editor.value) return;

    // Use Dhivehi placeholders when RTL is enabled
    const isRtlMode = props.rtl;
    const defaultPlaceholder = isRtlMode ? dhivehiPlaceholders.default : (props.placeholder || 'Start writing your content...');
    const headerPlaceholder = isRtlMode ? dhivehiPlaceholders.header : 'Enter a header';
    const quotePlaceholder = isRtlMode ? dhivehiPlaceholders.quote : 'Enter a quote';
    const quoteCaptionPlaceholder = isRtlMode ? dhivehiPlaceholders.quoteCaption : 'Quote author';
    const codePlaceholder = isRtlMode ? dhivehiPlaceholders.code : 'Enter code or recipe tips...';

    editor.value = new EditorJS({
        holder: editorRef.value,
        placeholder: defaultPlaceholder,
        readOnly: props.readOnly || false,
        data: props.modelValue || undefined,
        tools: {
            header: {
                class: Header,
                config: {
                    placeholder: headerPlaceholder,
                    levels: [2, 3, 4],
                    defaultLevel: 2,
                },
            },
            list: {
                class: List,
                inlineToolbar: true,
                config: {
                    defaultStyle: 'unordered',
                },
            },
            quote: {
                class: Quote,
                inlineToolbar: true,
                config: {
                    quotePlaceholder: quotePlaceholder,
                    captionPlaceholder: quoteCaptionPlaceholder,
                },
            },
            delimiter: Delimiter,
            table: {
                class: Table,
                inlineToolbar: true,
                config: {
                    rows: 2,
                    cols: 3,
                },
            },
            code: {
                class: Code,
                config: {
                    placeholder: codePlaceholder,
                },
            },
            media: {
                class: MediaBlock,
                config: {
                    placeholder: isRtlMode ? 'މީޑީއާ އެޑް ކުރެއްވުމަށް ކްލިކް ކުރައްވާ' : 'Click to add media',
                    onSelectMedia: props.onSelectMedia,
                },
            },
        },
        onChange: async () => {
            if (editor.value && isReady.value) {
                isInternalChange.value = true;
                const data = await editor.value.save();
                emit('update:modelValue', data);
                // Reset after a tick to allow the watch to skip
                setTimeout(() => {
                    isInternalChange.value = false;
                }, 0);
            }
        },
        onReady: () => {
            isReady.value = true;
        },
    });
};

// Only re-render if change came from outside (e.g., loading saved data)
watch(
    () => props.modelValue,
    async (newValue) => {
        // Skip if this change originated from the editor itself
        if (isInternalChange.value) return;

        if (editor.value && isReady.value && newValue) {
            await editor.value.render(newValue);
        }
    },
    { deep: true }
);

onMounted(() => {
    initEditor();
    // Add keydown listener for Dhivehi keyboard support
    if (editorRef.value) {
        editorRef.value.addEventListener('keydown', handleEditorKeyDown);
    }
});

onBeforeUnmount(() => {
    // Remove keydown listener
    if (editorRef.value) {
        editorRef.value.removeEventListener('keydown', handleEditorKeyDown);
    }
    if (editor.value) {
        editor.value.destroy();
        editor.value = null;
    }
});

// Expose editor for parent components if needed
defineExpose({
    getEditor: () => editor.value,
    save: async () => {
        if (editor.value) {
            return await editor.value.save();
        }
        return null;
    },
    clear: async () => {
        if (editor.value) {
            await editor.value.clear();
        }
    },
});
</script>

<template>
    <div class="block-editor" :class="{ 'block-editor--rtl': rtl }">
        <div
            ref="editorRef"
            class="block-editor__content"
            :dir="rtl ? 'rtl' : 'ltr'"
        />
    </div>
</template>

<style scoped>
.block-editor {
    background-color: transparent;
}

.block-editor__content {
    min-height: 300px;
}

.block-editor--rtl .block-editor__content {
    font-family: 'MV Typewriter', 'MV Faseyha', 'Faruma', sans-serif;
    text-align: right;
    font-size: 1.25rem;
    line-height: 2;
}
</style>

<style>
/* Editor.js styling overrides - unscoped for nested elements */
.codex-editor__redactor {
    padding-bottom: 6rem;
}

.ce-block__content,
.ce-toolbar__content {
    max-width: none;
    margin: 0;
}

/* Position toolbar on the left */
.ce-toolbar {
    left: -50px;
}

.ce-toolbar__content {
    max-width: none;
}

.ce-toolbar__actions {
    position: absolute;
    left: 0;
    right: auto;
}

.ce-paragraph,
.ce-header,
.cdx-list__item {
    color: var(--color-gray-900);
}

:root.dark .ce-paragraph,
:root.dark .ce-header,
:root.dark .cdx-list__item {
    color: var(--color-gray-100);
}

.cdx-quote__text {
    color: var(--color-gray-700);
    border-left: 4px solid var(--color-primary-500);
    padding-left: 1rem;
}

:root.dark .cdx-quote__text {
    color: var(--color-gray-300);
}

.cdx-quote__caption {
    color: var(--color-gray-500);
    font-size: 0.875rem;
}

:root.dark .cdx-quote__caption {
    color: var(--color-gray-400);
}

.ce-code__textarea {
    background-color: var(--color-gray-100);
    color: var(--color-gray-900);
    border-radius: 0.5rem;
}

:root.dark .ce-code__textarea {
    background-color: var(--color-gray-800);
    color: var(--color-gray-100);
}

.tc-table {
    border-collapse: collapse;
    width: 100%;
}

.tc-cell {
    border: 1px solid var(--color-gray-200);
    padding: 0.5rem;
    color: var(--color-gray-900);
}

:root.dark .tc-cell {
    border-color: var(--color-gray-700);
    color: var(--color-gray-100);
}

.ce-delimiter::before {
    color: var(--color-gray-400);
}

:root.dark .ce-delimiter::before {
    color: var(--color-gray-500);
}

/* Toolbar styling */
.ce-toolbar__plus,
.ce-toolbar__settings-btn {
    color: var(--color-gray-500);
}

:root.dark .ce-toolbar__plus,
:root.dark .ce-toolbar__settings-btn {
    color: var(--color-gray-400);
}

.ce-toolbar__plus:hover,
.ce-toolbar__settings-btn:hover {
    color: var(--color-gray-700);
}

:root.dark .ce-toolbar__plus:hover,
:root.dark .ce-toolbar__settings-btn:hover {
    color: var(--color-gray-200);
}

.ce-inline-toolbar {
    background-color: var(--color-white);
    border-color: var(--color-gray-200);
}

:root.dark .ce-inline-toolbar {
    background-color: var(--color-gray-800);
    border-color: var(--color-gray-700);
}

.ce-inline-tool {
    color: var(--color-gray-600);
}

:root.dark .ce-inline-tool {
    color: var(--color-gray-300);
}

.ce-inline-tool:hover {
    background-color: var(--color-gray-100);
}

:root.dark .ce-inline-tool:hover {
    background-color: var(--color-gray-700);
}

.ce-popover {
    background-color: var(--color-white);
    border-color: var(--color-gray-200);
}

:root.dark .ce-popover {
    background-color: var(--color-gray-800);
    border-color: var(--color-gray-700);
}

.ce-popover-item__title {
    color: var(--color-gray-900);
}

:root.dark .ce-popover-item__title {
    color: var(--color-gray-100);
}

.ce-popover-item:hover {
    background-color: var(--color-gray-100);
}

:root.dark .ce-popover-item:hover {
    background-color: var(--color-gray-700);
}

/* RTL Support */
.block-editor--rtl .ce-paragraph,
.block-editor--rtl .ce-header,
.block-editor--rtl .cdx-list__item,
.block-editor--rtl .cdx-quote__text,
.block-editor--rtl .cdx-quote__caption {
    text-align: right;
    direction: rtl;
    font-family: 'MV Typewriter', 'MV Faseyha', 'Faruma', sans-serif;
    font-size: 1.25rem;
    line-height: 2;
}

.block-editor--rtl .ce-header {
    font-size: 1.75rem;
    font-weight: 700;
}

.block-editor--rtl .cdx-quote__text {
    border-left: none;
    border-right: 4px solid var(--color-primary-500);
    padding-left: 0;
    padding-right: 1rem;
}

.block-editor--rtl .ce-toolbar {
    left: auto;
    right: -50px;
}

.block-editor--rtl .ce-toolbar__actions {
    left: auto;
    right: 0;
}
</style>
