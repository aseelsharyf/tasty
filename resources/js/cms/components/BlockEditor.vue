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
/* Uses Nuxt UI design system variables for consistency */

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

/* Text content */
.ce-paragraph,
.ce-header,
.cdx-list__item {
    color: var(--ui-text-highlighted);
}

.cdx-quote__text {
    color: var(--ui-text);
    border-left: 4px solid var(--ui-primary);
    padding-left: 1rem;
}

.cdx-quote__caption {
    color: var(--ui-text-muted);
    font-size: 0.875rem;
}

.ce-code__textarea {
    background-color: var(--ui-bg-elevated);
    color: var(--ui-text-highlighted);
    border: 1px solid var(--ui-border);
    border-radius: var(--ui-radius);
}

.tc-table {
    border-collapse: collapse;
    width: 100%;
}

.tc-cell {
    border: 1px solid var(--ui-border);
    padding: 0.5rem;
    color: var(--ui-text-highlighted);
}

.ce-delimiter::before {
    color: var(--ui-text-muted);
}

/* Toolbar buttons (+, settings) */
.ce-toolbar__plus,
.ce-toolbar__settings-btn {
    color: var(--ui-text-muted);
    background-color: var(--ui-bg);
    border: 1px solid var(--ui-border);
    border-radius: var(--ui-radius);
}

.ce-toolbar__plus:hover,
.ce-toolbar__settings-btn:hover {
    color: var(--ui-text-highlighted);
    background-color: var(--ui-bg-elevated);
}

/* Inline toolbar (bold, italic, link, etc.) */
.ce-inline-toolbar {
    background-color: var(--ui-bg);
    border: 1px solid var(--ui-border);
    border-radius: var(--ui-radius);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.dark .ce-inline-toolbar {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
}

.ce-inline-tool {
    color: var(--ui-text-muted);
}

.ce-inline-tool:hover {
    background-color: var(--ui-bg-elevated);
    color: var(--ui-text-highlighted);
}

.ce-inline-tool--active {
    color: var(--ui-primary);
}

/* Popover / Block menu (+ button dropdown) */
.ce-popover {
    background-color: var(--ui-bg);
    border: 1px solid var(--ui-border);
    border-radius: var(--ui-radius);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.dark .ce-popover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
}

.ce-popover__container {
    background-color: var(--ui-bg);
}

.ce-popover-item__title {
    color: var(--ui-text-highlighted);
}

.ce-popover-item__secondary-title {
    color: var(--ui-text-muted);
}

.ce-popover-item__icon,
.ce-popover-item__icon--tool {
    background-color: var(--ui-bg-elevated) !important;
    border: 1px solid var(--ui-border) !important;
    border-radius: var(--ui-radius) !important;
}

.ce-popover-item__icon svg,
.ce-popover-item__icon--tool svg {
    color: var(--ui-text-muted) !important;
}

.ce-popover-item:hover {
    background-color: var(--ui-bg-elevated);
}

.ce-popover-item:hover .ce-popover-item__icon,
.ce-popover-item:hover .ce-popover-item__icon--tool {
    background-color: var(--ui-bg-accented) !important;
    border-color: var(--ui-border-accented) !important;
}

.ce-popover-item:hover .ce-popover-item__icon svg,
.ce-popover-item:hover .ce-popover-item__icon--tool svg {
    color: var(--ui-text-highlighted) !important;
}

.ce-popover-item--focused {
    background-color: var(--ui-bg-elevated) !important;
}

/* Settings menu (gear icon dropdown) */
.ce-settings {
    background-color: var(--ui-bg);
    border: 1px solid var(--ui-border);
    border-radius: var(--ui-radius);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.dark .ce-settings {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
}

.cdx-settings-button {
    color: var(--ui-text-muted);
}

.cdx-settings-button:hover {
    background-color: var(--ui-bg-elevated);
    color: var(--ui-text-highlighted);
}

.cdx-settings-button--active {
    color: var(--ui-primary);
}

/* Conversion toolbar */
.ce-conversion-toolbar {
    background-color: var(--ui-bg);
    border: 1px solid var(--ui-border);
    border-radius: var(--ui-radius);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.dark .ce-conversion-toolbar {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
}

.ce-conversion-tool {
    color: var(--ui-text-muted);
}

.ce-conversion-tool:hover {
    background-color: var(--ui-bg-elevated);
    color: var(--ui-text-highlighted);
}

.ce-conversion-tool__icon {
    background-color: var(--ui-bg-elevated);
    border: 1px solid var(--ui-border);
}

/* Search input in block menu */
.cdx-search-field,
.ce-popover__search {
    background-color: var(--ui-bg-muted) !important;
    border: 1px solid var(--ui-border) !important;
    border-radius: var(--ui-radius) !important;
}

.cdx-search-field__input {
    color: var(--ui-text-highlighted) !important;
    background-color: transparent !important;
}

.cdx-search-field__input::placeholder {
    color: var(--ui-text-dimmed) !important;
}

.cdx-search-field__icon svg {
    color: var(--ui-text-muted) !important;
}

/* Block selection highlight */
.ce-block--selected .ce-block__content {
    background-color: var(--ui-bg-elevated);
}

/* Link tool input */
.ce-inline-tool-input {
    background-color: var(--ui-bg);
    color: var(--ui-text-highlighted);
    border: 1px solid var(--ui-border);
    border-radius: var(--ui-radius);
}

.ce-inline-tool-input::placeholder {
    color: var(--ui-text-dimmed);
}

/* Placeholder text */
.ce-paragraph[data-placeholder]:empty::before,
.ce-header[data-placeholder]:empty::before {
    color: var(--ui-text-dimmed);
}

/* Scrollbar for popover */
.ce-popover__items::-webkit-scrollbar {
    width: 6px;
}

.ce-popover__items::-webkit-scrollbar-track {
    background: var(--ui-bg-elevated);
}

.ce-popover__items::-webkit-scrollbar-thumb {
    background: var(--ui-border-accented);
    border-radius: 3px;
}

/* Nothing found message */
.ce-popover__nothing-found-message {
    color: var(--ui-text-muted);
}

/* Header tune popover */
.ce-popover--nested {
    background-color: var(--ui-bg);
    border: 1px solid var(--ui-border);
}

/* Popover item hover with border radius */
.ce-popover-item {
    border-radius: var(--ui-radius);
    transition: background-color 0.15s ease;
}

.ce-popover-item:hover .ce-popover-item__icon {
    background-color: var(--ui-bg-accented);
    border-color: var(--ui-border-accented);
}

/* Focus states / outlines */
.ce-toolbar__plus:focus,
.ce-toolbar__settings-btn:focus {
    outline: 2px solid var(--ui-primary);
    outline-offset: 2px;
}

.ce-inline-tool:focus {
    outline: none;
    background-color: var(--ui-bg-elevated);
}

.cdx-search-field:focus-within {
    border-color: var(--ui-border-accented);
    outline: none;
}

.ce-inline-tool-input:focus {
    outline: none;
    border-color: var(--ui-border-accented);
}

/* Block focus/hover states */
.ce-block:hover .ce-block__content {
    background-color: transparent;
}

.ce-block--focused {
    background-color: transparent;
}

/* Inline toolbar dropdown */
.ce-inline-toolbar__dropdown {
    background-color: var(--ui-bg);
    border: 1px solid var(--ui-border);
    border-radius: var(--ui-radius);
}

.ce-inline-toolbar__dropdown:hover {
    background-color: var(--ui-bg-elevated);
}

/* Conversion tool icon */
.ce-conversion-tool__icon svg {
    color: var(--ui-text-muted);
}

.ce-conversion-tool:hover .ce-conversion-tool__icon {
    background-color: var(--ui-bg-accented);
}

.ce-conversion-tool:hover .ce-conversion-tool__icon svg {
    color: var(--ui-text-highlighted);
}

/* Tune button (move up/down, delete) */
.ce-tune-btn {
    color: var(--ui-text-muted);
    background-color: transparent;
}

.ce-tune-btn:hover {
    background-color: var(--ui-bg-elevated);
    color: var(--ui-text-highlighted);
}

.ce-tune-btn--focused {
    background-color: var(--ui-bg-elevated);
}

/* Popover header/section titles */
.ce-popover-header {
    color: var(--ui-text-muted);
    border-bottom: 1px solid var(--ui-border);
}

/* List styling */
.cdx-list__item::before {
    color: var(--ui-text-muted);
}

/* Table controls */
.tc-toolbox {
    background-color: var(--ui-bg);
    border: 1px solid var(--ui-border);
    border-radius: var(--ui-radius);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.dark .tc-toolbox {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
}

.tc-toolbox__toggler {
    color: var(--ui-text-muted);
}

.tc-toolbox__toggler:hover {
    color: var(--ui-text-highlighted);
    background-color: var(--ui-bg-elevated);
}

.tc-add-column,
.tc-add-row {
    color: var(--ui-text-muted);
    background-color: var(--ui-bg);
    border: 1px solid var(--ui-border);
}

.tc-add-column:hover,
.tc-add-row:hover {
    color: var(--ui-text-highlighted);
    background-color: var(--ui-bg-elevated);
}

/* Code block */
.ce-code__textarea:focus {
    outline: none;
    border-color: var(--ui-border-accented);
}

/* Link inline tool - actions */
.ce-inline-tool-input--showed {
    border-color: var(--ui-border-accented);
}

/* Popover search clear button */
.cdx-search-field__icon--cross {
    color: var(--ui-text-muted);
}

.cdx-search-field__icon--cross:hover {
    color: var(--ui-text-highlighted);
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
    border-right: 4px solid var(--ui-primary);
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
