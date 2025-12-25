import type { BlockTool, BlockToolConstructorOptions, API, ToolConfig } from '@editorjs/editorjs';
import EditorJS, { type OutputData } from '@editorjs/editorjs';
import type { MediaBlockItem } from './MediaBlock';

/**
 * Collapsible block data structure
 */
export interface CollapsibleBlockData {
    title: string;
    content: OutputData;
    defaultExpanded: boolean;
}

interface CollapsibleBlockConfig {
    placeholder?: string;
    onSelectMedia?: (options: { multiple: boolean }) => Promise<MediaBlockItem[] | null>;
    getToolsConfig?: () => Record<string, ToolConfig>;
}

/**
 * Collapsible block with nested EditorJS support
 * Allows any block type inside a collapsible section
 */
export default class CollapsibleBlock implements BlockTool {
    private api: API;
    private data: CollapsibleBlockData;
    private config: CollapsibleBlockConfig;
    private wrapper: HTMLElement | null = null;
    private contentContainer: HTMLElement | null = null;
    private nestedEditor: EditorJS | null = null;
    private readOnly: boolean;
    private isExpanded: boolean = true;
    private cleanupObserver: MutationObserver | null = null;
    private nestedEditorReady: boolean = false;

    static get toolbox() {
        return {
            title: 'Collapsible',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>',
        };
    }

    static get isReadOnlySupported() {
        return true;
    }

    constructor({ data, config, api, readOnly }: BlockToolConstructorOptions<CollapsibleBlockData, CollapsibleBlockConfig>) {
        this.api = api;
        this.config = config || {};
        this.readOnly = readOnly || false;
        this.data = this.normalizeData(data);
        this.isExpanded = true; // Always expanded in editor for editing
    }

    private normalizeData(data: any): CollapsibleBlockData {
        return {
            title: data?.title || '',
            content: data?.content || { blocks: [] },
            defaultExpanded: data?.defaultExpanded ?? true,
        };
    }

    render(): HTMLElement {
        this.wrapper = document.createElement('div');
        this.wrapper.classList.add('ce-collapsible-block');

        // Header section with title and toggle
        const header = document.createElement('div');
        header.classList.add('ce-collapsible-block__header');

        // Prevent header from capturing clicks meant for children
        header.addEventListener('mousedown', (e) => {
            // Only stop if clicking directly on header, not on children
            if (e.target === header) {
                e.stopPropagation();
            }
        });

        // Title input - using an actual input element for better compatibility
        const titleInput = document.createElement('input');
        titleInput.type = 'text';
        titleInput.classList.add('ce-collapsible-block__title');
        titleInput.placeholder = this.config.placeholder || 'Enter section title...';
        titleInput.value = this.data.title || '';
        titleInput.readOnly = this.readOnly;

        // Save on input
        titleInput.addEventListener('input', () => {
            this.data.title = titleInput.value;
        });

        // Prevent EditorJS from capturing events - use capture phase
        const stopAllPropagation = (e: Event) => {
            e.stopPropagation();
            e.stopImmediatePropagation();
        };

        // Critical: Stop all keyboard events
        ['keydown', 'keyup', 'keypress'].forEach(eventType => {
            titleInput.addEventListener(eventType, (e) => {
                if (eventType === 'keydown' && (e as KeyboardEvent).key === 'Enter') {
                    e.preventDefault();
                }
                stopAllPropagation(e);
            }, true);
        });

        // Critical: Stop all mouse/pointer events
        ['mousedown', 'mouseup', 'click', 'pointerdown', 'pointerup'].forEach(eventType => {
            titleInput.addEventListener(eventType, (e) => {
                stopAllPropagation(e);
                // Force focus on click
                if (eventType === 'click' || eventType === 'pointerdown') {
                    titleInput.focus();
                }
            }, true);
        });

        // Stop focus/blur events
        ['focus', 'blur', 'focusin', 'focusout'].forEach(eventType => {
            titleInput.addEventListener(eventType, stopAllPropagation, true);
        });

        // Stop other events
        ['paste', 'copy', 'cut', 'select', 'selectstart'].forEach(eventType => {
            titleInput.addEventListener(eventType, stopAllPropagation, true);
        });

        header.appendChild(titleInput);

        // Toggle button
        const toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.classList.add('ce-collapsible-block__toggle');
        toggleBtn.title = 'Toggle content';
        this.updateToggleIcon(toggleBtn);
        toggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleContent();
            this.updateToggleIcon(toggleBtn);
        });
        header.appendChild(toggleBtn);

        this.wrapper.appendChild(header);

        // Content container for nested editor
        this.contentContainer = document.createElement('div');
        this.contentContainer.classList.add('ce-collapsible-block__content');
        this.wrapper.appendChild(this.contentContainer);

        // Initialize nested editor after DOM is ready
        setTimeout(() => this.initNestedEditor(), 0);

        // Setup cleanup observer
        this.setupCleanupObserver();

        return this.wrapper;
    }

    private updateToggleIcon(button: HTMLElement): void {
        if (this.isExpanded) {
            // Minus icon when expanded
            button.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14"/>
                </svg>
            `;
        } else {
            // Plus icon when collapsed
            button.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
            `;
        }
    }

    private toggleContent(): void {
        this.isExpanded = !this.isExpanded;
        if (this.contentContainer) {
            this.contentContainer.classList.toggle('ce-collapsible-block__content--collapsed', !this.isExpanded);
        }
    }

    private async initNestedEditor(): Promise<void> {
        if (!this.contentContainer || this.nestedEditor) return;

        const tools = this.getNestedTools();

        this.nestedEditor = new EditorJS({
            holder: this.contentContainer,
            data: this.data.content || { blocks: [] },
            minHeight: 50,
            readOnly: this.readOnly,
            tools: tools,
            placeholder: 'Add content here...',
            onChange: async () => {
                // Only save after editor is ready to avoid false dirty state on init
                if (this.nestedEditor && this.nestedEditorReady) {
                    try {
                        this.data.content = await this.nestedEditor.save();
                    } catch (error) {
                        console.error('CollapsibleBlock: Failed to save nested content', error);
                    }
                }
            },
            onReady: () => {
                // Mark ready after a short delay to skip initial onChange events
                setTimeout(() => {
                    this.nestedEditorReady = true;
                }, 100);
            },
        });
    }

    private getNestedTools(): Record<string, ToolConfig> {
        // Get tools from config if provided
        if (this.config.getToolsConfig) {
            return this.config.getToolsConfig();
        }

        // Return empty tools if no config provided
        // The parent should provide the tools config
        console.warn('CollapsibleBlock: No tools config provided. Nested editor will have limited functionality.');
        return {};
    }

    private setupCleanupObserver(): void {
        if (!this.wrapper) return;

        this.cleanupObserver = new MutationObserver((mutations) => {
            for (const mutation of mutations) {
                mutation.removedNodes.forEach((node) => {
                    if (node === this.wrapper || (node as Element).contains?.(this.wrapper as Node)) {
                        this.cleanup();
                        this.cleanupObserver?.disconnect();
                    }
                });
            }
        });

        // Observe the editor root for block removal
        const editorRoot = this.wrapper.closest('.codex-editor');
        if (editorRoot) {
            this.cleanupObserver.observe(editorRoot, { childList: true, subtree: true });
        }
    }

    private cleanup(): void {
        if (this.nestedEditor) {
            try {
                this.nestedEditor.destroy();
            } catch (error) {
                // Ignore cleanup errors
            }
            this.nestedEditor = null;
        }
        if (this.cleanupObserver) {
            this.cleanupObserver.disconnect();
            this.cleanupObserver = null;
        }
    }

    async save(): Promise<CollapsibleBlockData> {
        // Ensure nested content is saved
        if (this.nestedEditor) {
            try {
                this.data.content = await this.nestedEditor.save();
            } catch (error) {
                console.error('CollapsibleBlock: Failed to save nested content', error);
            }
        }

        return {
            title: this.data.title,
            content: this.data.content,
            defaultExpanded: this.data.defaultExpanded,
        };
    }

    validate(data: CollapsibleBlockData): boolean {
        // Allow empty title but require at least some indication of content
        return true;
    }

    static get sanitize() {
        return {
            title: true, // Plain text only for input element
        };
    }

    renderSettings(): HTMLElement {
        const wrapper = document.createElement('div');
        wrapper.classList.add('ce-collapsible-block__settings');

        // Default expanded toggle
        const expandedGroup = document.createElement('div');
        expandedGroup.classList.add('ce-collapsible-block__settings-group');

        const expandedOptions = [
            {
                value: true,
                label: 'Expanded by default',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>',
            },
            {
                value: false,
                label: 'Collapsed by default',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>',
            },
        ];

        expandedOptions.forEach(({ value, icon, label }) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.classList.add('cdx-settings-button');
            button.classList.toggle('cdx-settings-button--active', this.data.defaultExpanded === value);
            button.innerHTML = icon;
            button.title = label;
            button.addEventListener('click', () => {
                this.data.defaultExpanded = value;
                // Update active state
                expandedGroup.querySelectorAll('.cdx-settings-button').forEach((btn) => {
                    btn.classList.remove('cdx-settings-button--active');
                });
                button.classList.add('cdx-settings-button--active');
            });
            expandedGroup.appendChild(button);
        });

        wrapper.appendChild(expandedGroup);

        return wrapper;
    }
}
