<script setup lang="ts">
import { ref, watch, onMounted, onBeforeUnmount, shallowRef, computed } from 'vue';
import EditorJS, { type OutputData } from '@editorjs/editorjs';
import Header from '@editorjs/header';
import List from '@editorjs/list';
import Delimiter from '@editorjs/delimiter';
import Table from '@editorjs/table';
import Code from '@editorjs/code';
import LinkTool from '@editorjs/link';
import MediaBlock, { type MediaBlockItem, type FocalPoint } from '../editor-tools/MediaBlock';
import QuoteBlock from '../editor-tools/QuoteBlock';
import CollapsibleBlock from '../editor-tools/CollapsibleBlock';
import PostsBlock, { type PostBlockItem } from '../editor-tools/PostsBlock';
import '../editor-tools/media-block.css';
import '../editor-tools/quote-block.css';
import '../editor-tools/collapsible-block.css';
import '../editor-tools/posts-block.css';
import type { DhivehiLayout } from '../composables/useDhivehiKeyboard';

// Media selection callback type
export type MediaSelectCallback = (options: { multiple: boolean }) => Promise<MediaBlockItem[] | null>;

// Post selection callback type
export type PostSelectCallback = () => Promise<PostBlockItem[] | null>;

// Focal point callback type
export type FocalPointCallback = (imageUrl: string, currentFocalPoint: FocalPoint | null) => Promise<FocalPoint | null>;

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
    onSelectPosts?: PostSelectCallback;
    onSetFocalPoint?: FocalPointCallback;
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

// Handle paste to strip most formatting but keep bold and italic
function handlePaste(e: ClipboardEvent): void {
    const html = e.clipboardData?.getData('text/html');
    const text = e.clipboardData?.getData('text/plain') || '';

    // If no HTML, let Editor.js handle plain text paste normally
    if (!html) {
        return;
    }

    e.preventDefault();
    e.stopPropagation();
    e.stopImmediatePropagation();

    // Parse HTML and strip everything except bold/italic
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, 'text/html');

    // Function to clean HTML recursively, keeping only b, strong, i, em tags
    function cleanNode(node: Node): string {
        if (node.nodeType === Node.TEXT_NODE) {
            return node.textContent || '';
        }

        if (node.nodeType === Node.ELEMENT_NODE) {
            const el = node as Element;
            const tagName = el.tagName.toLowerCase();
            const children = Array.from(el.childNodes).map(cleanNode).join('');

            // Keep bold and italic tags
            if (tagName === 'b' || tagName === 'strong') {
                return `<b>${children}</b>`;
            }
            if (tagName === 'i' || tagName === 'em') {
                return `<i>${children}</i>`;
            }

            // For block elements, add line breaks
            if (['p', 'div', 'br', 'li'].includes(tagName)) {
                return children + '\n';
            }

            // Strip all other tags but keep content
            return children;
        }

        return '';
    }

    const cleanedHtml = cleanNode(doc.body).trim();

    if (cleanedHtml) {
        // Insert as HTML to preserve bold/italic
        document.execCommand('insertHTML', false, cleanedHtml);
    } else if (text) {
        // Fallback to plain text
        document.execCommand('insertText', false, text);
    }
}

// Dhivehi translations for placeholders
const dhivehiPlaceholders = {
    default: 'ލިޔަން ފަށާ...', // Start writing...
    header: 'ސުރުޚީ', // Header
    quote: 'ބަސްކޮޅެއް ލިޔޭ', // Enter a quote
    quoteCaption: 'ބުނީ ކާކު؟', // Who said it?
    code: 'ކޯޑު ނުވަތަ ރެސިޕީ ޓިޕްސް...', // Code or recipe tips...
};

// Normalize EditorJS data to handle version differences
// List tool v2.x expects nested format: { content: string, items: [] }
// Older data might have plain strings: ['item1', 'item2']
// IMPORTANT: We use JSON.parse(JSON.stringify()) to deep clone and remove
// Vue reactive proxies, which cause structuredClone errors in EditorJS
function normalizeEditorData(data: any): any {
    if (!data || !data.blocks) return data;

    // Deep clone to remove reactive proxies that cause structuredClone errors
    const plainData = JSON.parse(JSON.stringify(data));

    return {
        ...plainData,
        blocks: plainData.blocks.map((block: any) => {
            if (block.type === 'list' && Array.isArray(block.data?.items)) {
                return {
                    ...block,
                    data: {
                        style: block.data.style || 'unordered',
                        meta: {},
                        items: normalizeListItems(block.data.items)
                    }
                };
            }
            return block;
        })
    };
}

// Recursively normalize list items to ensure proper format for List v2.x
function normalizeListItems(items: any[]): any[] {
    return items.map((item: any) => {
        // If item is a string, convert to nested format
        if (typeof item === 'string') {
            return {
                content: item,
                meta: {},
                items: []
            };
        }
        // If item is already an object
        if (typeof item === 'object' && item !== null) {
            return {
                content: String(item.content || ''),
                meta: {},
                items: Array.isArray(item.items) ? normalizeListItems(item.items) : []
            };
        }
        // Fallback for unexpected types
        return {
            content: '',
            meta: {},
            items: []
        };
    });
}

const initEditor = async () => {
    if (!editorRef.value || editor.value) return;

    // Use Dhivehi placeholders when RTL is enabled
    const isRtlMode = props.rtl;
    const defaultPlaceholder = isRtlMode ? dhivehiPlaceholders.default : (props.placeholder || 'Start writing your content...');
    const headerPlaceholder = isRtlMode ? dhivehiPlaceholders.header : 'Enter a header';
    const quotePlaceholder = isRtlMode ? dhivehiPlaceholders.quote : 'Enter a quote';
    const quoteCaptionPlaceholder = isRtlMode ? dhivehiPlaceholders.quoteCaption : 'Quote author';
    const codePlaceholder = isRtlMode ? dhivehiPlaceholders.code : 'Enter code or recipe tips...';

    // Normalize data to handle version differences (e.g., List tool v2.x format)
    const normalizedData = props.modelValue ? normalizeEditorData(props.modelValue) : undefined;

    editor.value = new EditorJS({
        holder: editorRef.value,
        placeholder: defaultPlaceholder,
        readOnly: props.readOnly || false,
        minHeight: 0,
        data: normalizedData,
        // Define which inline tools are available globally
        inlineToolbar: ['bold', 'italic', 'link'],
        tools: {
            // Order: Text (default paragraph), Header, Media, Quote, Collapsible, then others
            header: {
                class: Header,
                config: {
                    placeholder: headerPlaceholder,
                    levels: [2, 3, 4],
                    defaultLevel: 2,
                },
            },
            media: {
                class: MediaBlock,
                config: {
                    placeholder: isRtlMode ? 'މީޑީއާ އެޑް ކުރެއްވުމަށް ކްލިކް ކުރައްވާ' : 'Click to add media',
                    onSelectMedia: props.onSelectMedia,
                    onSetFocalPoint: props.onSetFocalPoint,
                },
            },
            quote: {
                class: QuoteBlock,
                inlineToolbar: ['bold', 'italic'],
                config: {
                    quotePlaceholder: quotePlaceholder,
                    captionPlaceholder: quoteCaptionPlaceholder,
                    authorTitlePlaceholder: isRtlMode ? 'މަގާމު ނުވަތަ ޓައިޓަލް' : 'Title or role',
                    onSelectMedia: props.onSelectMedia,
                },
            },
            collapsible: {
                class: CollapsibleBlock,
                config: {
                    placeholder: isRtlMode ? 'ސެކްޝަން ޓައިޓަލް...' : 'Enter section title...',
                    onSelectMedia: props.onSelectMedia,
                    // Provide tools for nested editor (excluding collapsible to prevent infinite nesting)
                    getToolsConfig: () => ({
                        header: {
                            class: Header,
                            config: {
                                placeholder: headerPlaceholder,
                                levels: [2, 3, 4],
                                defaultLevel: 3,
                            },
                        },
                        media: {
                            class: MediaBlock,
                            config: {
                                placeholder: isRtlMode ? 'މީޑީއާ އެޑް ކުރެއްވުމަށް ކްލިކް ކުރައްވާ' : 'Click to add media',
                                onSelectMedia: props.onSelectMedia,
                                onSetFocalPoint: props.onSetFocalPoint,
                            },
                        },
                        quote: {
                            class: QuoteBlock,
                            inlineToolbar: ['bold', 'italic'],
                            config: {
                                quotePlaceholder: quotePlaceholder,
                                captionPlaceholder: quoteCaptionPlaceholder,
                                authorTitlePlaceholder: isRtlMode ? 'މަގާމު ނުވަތަ ޓައިޓަލް' : 'Title or role',
                                onSelectMedia: props.onSelectMedia,
                            },
                        },
                        linkTool: {
                            class: LinkTool,
                            config: {
                                endpoint: '/cms/api/fetch-url',
                            },
                        },
                        list: {
                            class: List,
                            inlineToolbar: true,
                            config: {
                                defaultStyle: 'unordered',
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
                        posts: {
                            class: PostsBlock,
                            config: {
                                placeholder: isRtlMode ? 'ޕੋސްޓް އެޑް ކުރެއްވުމަށް ކްލިކް ކުރައްވާ' : 'Click to add posts',
                                onSelectPosts: props.onSelectPosts,
                            },
                        },
                    }),
                },
            },
            linkTool: {
                class: LinkTool,
                config: {
                    endpoint: '/cms/api/fetch-url',
                },
            },
            list: {
                class: List,
                inlineToolbar: true,
                config: {
                    defaultStyle: 'unordered',
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
            posts: {
                class: PostsBlock,
                config: {
                    placeholder: isRtlMode ? 'ޕੋސްޓް އެޑް ކުރެއްވުމަށް ކްލިކް ކުރައްވާ' : 'Click to add posts',
                    onSelectPosts: props.onSelectPosts,
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
// We use a simple flag approach - once the editor is ready and has content,
// we don't re-render from external changes unless it's a complete data replacement
const hasInitialContent = ref(false);

watch(
    () => props.modelValue,
    async (newValue, oldValue) => {
        // Skip if this change originated from the editor itself
        if (isInternalChange.value) return;

        // Skip if editor already has content (user is editing)
        // Only allow re-render if going from no content to content (initial load)
        if (hasInitialContent.value && editor.value && isReady.value) {
            return;
        }

        if (editor.value && isReady.value && newValue) {
            // Normalize data before rendering to handle version differences
            const normalizedData = normalizeEditorData(newValue);
            await editor.value.render(normalizedData);
            hasInitialContent.value = true;
        }
    },
    { deep: true }
);

onMounted(() => {
    initEditor();
    // Add keydown listener for Dhivehi keyboard support
    // Add paste listener to strip formatting (capture phase to intercept before Editor.js)
    if (editorRef.value) {
        editorRef.value.addEventListener('keydown', handleEditorKeyDown);
        editorRef.value.addEventListener('paste', handlePaste, true);
    }
});

onBeforeUnmount(() => {
    // Remove event listeners
    if (editorRef.value) {
        editorRef.value.removeEventListener('keydown', handleEditorKeyDown);
        editorRef.value.removeEventListener('paste', handlePaste, true);
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
    padding-bottom: 2rem !important;
}

.ce-block__content,
.ce-toolbar__content {
    max-width: none;
    margin: 0;
}

/* Position toolbar on the left */
.ce-toolbar {
    left: -70px;
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

/* Header sizing based on level */
h2.ce-header {
    font-size: 35px;
    line-height: 40px;
    letter-spacing: -1.4px;
    font-weight: 600;
}

h3.ce-header {
    font-size: 40px;
    line-height: 45px;
    letter-spacing: -1.6px;
    font-weight: 600;
}

h4.ce-header {
    font-size: 45px;
    line-height: 50px;
    letter-spacing: -1.8px;
    font-weight: 600;
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
.block-editor--rtl .cdx-list__item {
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

.block-editor--rtl .ce-toolbar {
    left: auto;
    right: -70px;
}

.block-editor--rtl .ce-toolbar__actions {
    left: auto;
    right: 0;
}

/* Link Tool - prevent image hiding in narrow mode */
.codex-editor--narrow .link-tool__image {
    display: block !important;
}

/* Mobile: Constrain block picker popover to viewport */
@media (max-width: 640px) {
    .ce-popover {
        position: fixed !important;
        left: 1rem !important;
        right: 1rem !important;
        bottom: 1rem !important;
        top: auto !important;
        max-height: 60vh !important;
        width: auto !important;
        transform: none !important;
    }

    .ce-popover__container {
        max-height: 100%;
        overflow-y: auto;
    }

    .ce-popover__items {
        max-height: calc(60vh - 60px);
    }

    /* Inline toolbar positioning on mobile */
    .ce-inline-toolbar {
        left: 0.5rem !important;
        right: 0.5rem !important;
        width: auto !important;
    }

    /* Settings popover on mobile */
    .ce-settings {
        position: fixed !important;
        left: 1rem !important;
        right: 1rem !important;
        bottom: 1rem !important;
        top: auto !important;
        max-height: 50vh !important;
        width: auto !important;
    }

    /* Conversion toolbar on mobile */
    .ce-conversion-toolbar {
        left: 1rem !important;
        right: 1rem !important;
        width: auto !important;
    }

    /* Toolbar positioning on mobile - inline instead of left-positioned */
    .ce-toolbar {
        left: 0 !important;
    }

    .ce-toolbar__actions {
        position: static;
        display: flex;
        gap: 0.25rem;
    }

    .ce-toolbar__plus,
    .ce-toolbar__settings-btn {
        width: 28px;
        height: 28px;
    }

    .block-editor--rtl .ce-toolbar {
        right: 0 !important;
        left: auto !important;
    }
}
</style>
