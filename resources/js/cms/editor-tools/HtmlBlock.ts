import type { BlockTool, BlockToolConstructorOptions, API } from '@editorjs/editorjs';

/**
 * HTML Embed block data structure
 */
export interface HtmlBlockData {
    html: string;
}

interface HtmlBlockConfig {
    placeholder?: string;
}

/**
 * HTML Embed block - allows users to paste any HTML/embed code and preview it
 */
export default class HtmlBlock implements BlockTool {
    private api: API;
    private data: HtmlBlockData;
    private config: HtmlBlockConfig;
    private wrapper: HTMLElement | null = null;
    private textarea: HTMLTextAreaElement | null = null;
    private preview: HTMLElement | null = null;
    private readOnly: boolean;
    private showingPreview: boolean = false;

    static get toolbox() {
        return {
            title: 'HTML Embed',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>',
        };
    }

    static get isReadOnlySupported(): boolean {
        return true;
    }

    constructor({ data, config, api, readOnly }: BlockToolConstructorOptions<HtmlBlockData, HtmlBlockConfig>) {
        this.api = api;
        this.config = config || {};
        this.readOnly = readOnly || false;
        this.data = {
            html: data?.html || '',
        };
    }

    render(): HTMLElement {
        this.wrapper = document.createElement('div');
        this.wrapper.classList.add('ce-html-block');

        if (this.readOnly) {
            this.renderPreviewOnly();
            return this.wrapper;
        }

        // Header with label and toggle button
        const header = document.createElement('div');
        header.classList.add('ce-html-block__header');

        const label = document.createElement('span');
        label.classList.add('ce-html-block__label');
        label.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg> HTML Embed';

        const toggleBtn = document.createElement('button');
        toggleBtn.classList.add('ce-html-block__toggle');
        toggleBtn.type = 'button';
        toggleBtn.textContent = 'Preview';
        toggleBtn.addEventListener('click', () => this.togglePreview());

        header.appendChild(label);
        header.appendChild(toggleBtn);

        // Code textarea
        this.textarea = document.createElement('textarea');
        this.textarea.classList.add('ce-html-block__textarea');
        this.textarea.placeholder = this.config.placeholder || 'Paste your HTML or embed code here...';
        this.textarea.value = this.data.html;

        // Auto-resize textarea
        this.textarea.addEventListener('input', () => {
            this.autoResize();
            this.data.html = this.textarea!.value;
        });

        // Preview container (hidden initially)
        this.preview = document.createElement('div');
        this.preview.classList.add('ce-html-block__preview');
        this.preview.style.display = 'none';

        this.wrapper.appendChild(header);
        this.wrapper.appendChild(this.textarea);
        this.wrapper.appendChild(this.preview);

        // Auto-resize after render
        requestAnimationFrame(() => this.autoResize());

        return this.wrapper;
    }

    private renderPreviewOnly(): void {
        if (!this.wrapper || !this.data.html) return;

        const preview = document.createElement('div');
        preview.classList.add('ce-html-block__preview', 'ce-html-block__preview--active');
        preview.innerHTML = this.data.html;

        this.wrapper.appendChild(preview);
    }

    private togglePreview(): void {
        if (!this.textarea || !this.preview) return;

        this.showingPreview = !this.showingPreview;

        const toggleBtn = this.wrapper?.querySelector('.ce-html-block__toggle');

        if (this.showingPreview) {
            this.textarea.style.display = 'none';
            this.preview.style.display = 'block';
            this.preview.innerHTML = this.data.html || '<p style="color: var(--ui-text-dimmed); font-style: italic;">No HTML to preview</p>';
            if (toggleBtn) toggleBtn.textContent = 'Code';
        } else {
            this.textarea.style.display = 'block';
            this.preview.style.display = 'none';
            if (toggleBtn) toggleBtn.textContent = 'Preview';
            requestAnimationFrame(() => this.autoResize());
        }
    }

    private autoResize(): void {
        if (!this.textarea) return;
        this.textarea.style.height = 'auto';
        this.textarea.style.height = Math.max(120, this.textarea.scrollHeight) + 'px';
    }

    save(): HtmlBlockData {
        return {
            html: this.data.html,
        };
    }

    validate(savedData: HtmlBlockData): boolean {
        return savedData.html.trim().length > 0;
    }
}
