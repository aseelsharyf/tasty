import type { BlockTool, BlockToolConstructorOptions, API } from '@editorjs/editorjs';
import type { MediaBlockItem } from './MediaBlock';

/**
 * Quote block data structure
 */
export interface QuoteBlockData {
    text: string;
    caption: string;
    alignment: 'left' | 'center';
    author?: {
        name: string;
        title?: string;
        photo?: MediaBlockItem | null;
    };
}

interface QuoteBlockConfig {
    quotePlaceholder?: string;
    captionPlaceholder?: string;
    authorNamePlaceholder?: string;
    authorTitlePlaceholder?: string;
    onSelectMedia?: (options: { multiple: boolean }) => Promise<MediaBlockItem[] | null>;
}

/**
 * Custom Quote block with optional author photo support
 */
export default class QuoteBlock implements BlockTool {
    private api: API;
    private data: QuoteBlockData;
    private config: QuoteBlockConfig;
    private wrapper: HTMLElement | null = null;
    private readOnly: boolean;

    static get toolbox() {
        return {
            title: 'Quote',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V21c0 1 0 1 1 1z"/><path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2h.75c0 2.25.25 4-2.75 4v3c0 1 0 1 1 1z"/></svg>',
        };
    }

    static get isReadOnlySupported() {
        return true;
    }

    static get conversionConfig() {
        return {
            import: 'text',
            export: (data: QuoteBlockData) => data.text,
        };
    }

    constructor({ data, config, api, readOnly }: BlockToolConstructorOptions<QuoteBlockData, QuoteBlockConfig>) {
        this.api = api;
        this.config = config || {};
        this.readOnly = readOnly || false;
        this.data = this.normalizeData(data);
    }

    private normalizeData(data: any): QuoteBlockData {
        return {
            text: data?.text || '',
            caption: data?.caption || '',
            alignment: data?.alignment || 'left',
            author: data?.author || undefined,
        };
    }

    render(): HTMLElement {
        this.wrapper = document.createElement('div');
        this.wrapper.classList.add('ce-quote-block');
        this.wrapper.classList.add(`ce-quote-block--${this.data.alignment}`);

        // Quote text
        const quoteText = document.createElement('blockquote');
        quoteText.classList.add('ce-quote-block__text');
        quoteText.contentEditable = this.readOnly ? 'false' : 'true';
        quoteText.dataset.placeholder = this.config.quotePlaceholder || 'Enter a quote';
        quoteText.innerHTML = this.data.text;
        quoteText.addEventListener('input', () => {
            this.data.text = quoteText.innerHTML;
        });
        quoteText.addEventListener('keydown', (e) => e.stopPropagation());
        this.wrapper.appendChild(quoteText);

        // Author section
        const authorSection = document.createElement('div');
        authorSection.classList.add('ce-quote-block__author');

        // Author photo
        const photoWrapper = document.createElement('div');
        photoWrapper.classList.add('ce-quote-block__photo-wrapper');
        this.renderAuthorPhoto(photoWrapper);
        authorSection.appendChild(photoWrapper);

        // Author info
        const authorInfo = document.createElement('div');
        authorInfo.classList.add('ce-quote-block__author-info');

        // Author name (uses caption field for backward compatibility)
        const authorName = document.createElement('cite');
        authorName.classList.add('ce-quote-block__caption');
        authorName.contentEditable = this.readOnly ? 'false' : 'true';
        authorName.dataset.placeholder = this.config.captionPlaceholder || 'Quote author';
        authorName.innerHTML = this.data.caption || this.data.author?.name || '';
        authorName.addEventListener('input', () => {
            this.data.caption = authorName.innerHTML;
            if (this.data.author) {
                this.data.author.name = authorName.textContent || '';
            }
        });
        authorName.addEventListener('keydown', (e) => e.stopPropagation());
        authorInfo.appendChild(authorName);

        // Author title (optional)
        if (this.data.author?.title || !this.readOnly) {
            const authorTitle = document.createElement('span');
            authorTitle.classList.add('ce-quote-block__author-title');
            authorTitle.contentEditable = this.readOnly ? 'false' : 'true';
            authorTitle.dataset.placeholder = this.config.authorTitlePlaceholder || 'Title or role';
            authorTitle.innerHTML = this.data.author?.title || '';
            authorTitle.addEventListener('input', () => {
                if (!this.data.author) {
                    this.data.author = { name: this.data.caption };
                }
                this.data.author.title = authorTitle.innerHTML;
            });
            authorTitle.addEventListener('keydown', (e) => e.stopPropagation());
            authorInfo.appendChild(authorTitle);
        }

        authorSection.appendChild(authorInfo);
        this.wrapper.appendChild(authorSection);

        return this.wrapper;
    }

    private renderAuthorPhoto(container: HTMLElement): void {
        container.innerHTML = '';

        if (this.data.author?.photo) {
            // Show photo
            const img = document.createElement('img');
            img.classList.add('ce-quote-block__photo');
            img.src = this.data.author.photo.thumbnail_url || this.data.author.photo.url;
            img.alt = this.data.author.photo.alt_text || 'Author photo';
            container.appendChild(img);

            // Remove button
            if (!this.readOnly) {
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.classList.add('ce-quote-block__photo-remove');
                removeBtn.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                `;
                removeBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    if (this.data.author) {
                        this.data.author.photo = null;
                    }
                    this.renderAuthorPhoto(container);
                });
                container.appendChild(removeBtn);
            }
        } else if (!this.readOnly) {
            // Show add photo button
            const addBtn = document.createElement('button');
            addBtn.type = 'button';
            addBtn.classList.add('ce-quote-block__photo-add');
            addBtn.title = 'Add author photo';
            addBtn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="8" r="4"/>
                    <path d="M20 21a8 8 0 0 0-16 0"/>
                </svg>
            `;
            addBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.selectAuthorPhoto(container);
            });
            container.appendChild(addBtn);
        }
    }

    private async selectAuthorPhoto(container: HTMLElement): Promise<void> {
        if (!this.config.onSelectMedia) {
            console.warn('QuoteBlock: onSelectMedia callback not configured');
            return;
        }

        try {
            const selected = await this.config.onSelectMedia({ multiple: false });
            if (selected && selected.length > 0) {
                if (!this.data.author) {
                    this.data.author = { name: this.data.caption };
                }
                this.data.author.photo = selected[0];
                this.renderAuthorPhoto(container);
            }
        } catch (error) {
            console.error('QuoteBlock: Failed to select media', error);
        }
    }

    save(): QuoteBlockData {
        return this.data;
    }

    static get sanitize() {
        return {
            text: {
                br: true,
                i: true,
                em: true,
                b: true,
                strong: true,
            },
            caption: {
                br: true,
            },
        };
    }

    renderSettings(): HTMLElement {
        const wrapper = document.createElement('div');
        wrapper.classList.add('ce-quote-block__settings');

        // Alignment settings
        const alignments: Array<{ value: 'left' | 'center'; icon: string; label: string }> = [
            {
                value: 'left',
                label: 'Align left',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="21" x2="3" y1="6" y2="6"/><line x1="15" x2="3" y1="12" y2="12"/><line x1="17" x2="3" y1="18" y2="18"/></svg>',
            },
            {
                value: 'center',
                label: 'Align center',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="21" x2="3" y1="6" y2="6"/><line x1="18" x2="6" y1="12" y2="12"/><line x1="21" x2="3" y1="18" y2="18"/></svg>',
            },
        ];

        alignments.forEach(({ value, icon, label }) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.classList.add('cdx-settings-button');
            button.classList.toggle('cdx-settings-button--active', this.data.alignment === value);
            button.innerHTML = icon;
            button.title = label;
            button.addEventListener('click', () => {
                this.data.alignment = value;
                if (this.wrapper) {
                    this.wrapper.classList.remove('ce-quote-block--left', 'ce-quote-block--center');
                    this.wrapper.classList.add(`ce-quote-block--${value}`);
                }
                // Update active state
                wrapper.querySelectorAll('.cdx-settings-button').forEach((btn) => {
                    btn.classList.remove('cdx-settings-button--active');
                });
                button.classList.add('cdx-settings-button--active');
            });
            wrapper.appendChild(button);
        });

        return wrapper;
    }
}
